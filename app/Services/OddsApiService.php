<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OddsApiService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.the-odds-api.com/v4';
    
    // Map our leagues to API leagues with proper team mappings
    protected $leagueMap = [
        'austria_2' => [
            'api_league' => 'soccer_austria_bundesliga',
            'teams' => [
                'st. polten' => ['SKN St. Polten', 'St. Polten', 'St. Pölten'],
                'rapid' => ['SK Rapid II', 'Rapid Wien II', 'Rapid Vienna II', 'Rapid II'],
                'rapid ii' => ['SK Rapid II', 'Rapid Wien II', 'Rapid Vienna II', 'Rapid II'],
                'rapid wien ii' => ['SK Rapid II', 'Rapid Wien II', 'Rapid Vienna II', 'Rapid II'],
                'rapid vienna ii' => ['SK Rapid II', 'Rapid Wien II', 'Rapid Vienna II', 'Rapid II'],
                'bregenz' => ['SC Bregenz', 'Bregenz'],
                'ried' => ['SV Ried', 'Ried'],
            ]
        ],
        'belgium' => [
            'api_league' => 'soccer_belgium_first_div',
            'teams' => [
                'leuven' => ['OH Leuven', 'Leuven'],
                'westerlo' => ['Westerlo', 'KVC Westerlo'],
            ]
        ],
        'china' => [
            'api_league' => 'soccer_china_superleague',
            'teams' => [
                'yunnan yukun' => ['Yunnan Yukun', 'Yunnan'],
                'meizhou hakka' => ['Meizhou Hakka', 'Meizhou'],
            ]
        ],
        'denmark' => [
            'api_league' => 'soccer_denmark_superliga',
            'teams' => [
                'norsjaelland' => ['FC Nordsjælland', 'Nordsjælland', 'Norsjaelland'],
                'aarhus' => ['Aarhus GF', 'Aarhus', 'AGF'],
            ]
        ],
        'england' => [
            'api_league' => 'soccer_epl',
            'teams' => []
        ],
        'germany' => [
            'api_league' => 'soccer_germany_bundesliga',
            'teams' => []
        ],
        'spain' => [
            'api_league' => 'soccer_spain_la_liga',
            'teams' => []
        ],
        'italy' => [
            'api_league' => 'soccer_italy_serie_a',
            'teams' => []
        ],
        'france' => [
            'api_league' => 'soccer_france_ligue_1',
            'teams' => []
        ],
        'netherlands' => [
            'api_league' => 'soccer_netherlands_eredivisie',
            'teams' => []
        ],
        'portugal' => [
            'api_league' => 'soccer_portugal_primeira_liga',
            'teams' => []
        ],
        'turkey' => [
            'api_league' => 'soccer_turkey_super_league',
            'teams' => []
        ],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.odds_api.key');
        if (empty($this->apiKey)) {
            Log::error('Odds API key is not configured');
        }
    }

    /**
     * Get odds for a specific match
     */
    public function getMatchOdds(string $match, string $date, string $league = 'soccer_epl')
    {
        try {
            // Map the league to API format
            $leagueConfig = $this->mapLeague($league);
            if (!$leagueConfig) {
                Log::error('Invalid league mapping', ['league' => $league]);
                return null;
            }

            $apiLeague = $leagueConfig['api_league'];
            
            // Log the request details
            Log::info('Fetching odds from API', [
                'match' => $match,
                'date' => $date,
                'league' => $league,
                'api_league' => $apiLeague
            ]);

            // Add delay to respect rate limits
            sleep(1);

            $response = Http::get("{$this->baseUrl}/sports/{$apiLeague}/odds", [
                'apiKey' => $this->apiKey,
                'regions' => 'eu',
                'markets' => 'h2h',
                'dateFormat' => 'iso',
                'oddsFormat' => 'decimal'
            ]);

            // Check remaining requests
            $remaining = $response->header('x-requests-remaining');
            $used = $response->header('x-requests-used');
            Log::info('Odds API Usage', [
                'remaining' => $remaining,
                'used' => $used
            ]);

            if (!$response->successful()) {
                Log::error('Odds API Error', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                    'match' => $match,
                    'date' => $date,
                    'league' => $apiLeague
                ]);
                return null;
            }

            $matches = $response->json();
            
            // Log raw API response for debugging
            Log::info('Raw API Response', [
                'response_type' => gettype($matches),
                'is_array' => is_array($matches),
                'count' => is_array($matches) ? count($matches) : 0,
                'first_match' => is_array($matches) && !empty($matches) ? $matches[0] : null
            ]);
            
            if (!is_array($matches) || empty($matches)) {
                Log::warning('No matches found in API response', [
                    'league' => $apiLeague,
                    'match' => $match,
                    'date' => $date,
                    'response' => $matches
                ]);
                return null;
            }

            // Log available matches for debugging
            Log::info('Available matches', [
                'count' => count($matches),
                'matches' => array_map(function($m) {
                    return [
                        'teams' => $m['home_team'] . ' vs ' . $m['away_team'],
                        'date' => $m['commence_time'],
                        'bookmakers' => count($m['bookmakers'] ?? [])
                    ];
                }, $matches)
            ]);

            return $this->findMatchingOdds($matches, $match, $date, $leagueConfig['teams']);

        } catch (\Exception $e) {
            Log::error('Odds API Exception', [
                'message' => $e->getMessage(),
                'match' => $match,
                'date' => $date,
                'league' => $league,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Map our league format to API league format
     */
    protected function mapLeague(string $league): ?array
    {
        if (!isset($this->leagueMap[$league])) {
            Log::warning('Unknown league', ['league' => $league]);
            return null;
        }
        return $this->leagueMap[$league];
    }

    /**
     * Find matching odds for a prediction
     */
    protected function findMatchingOdds(array $matches, string $predictionMatch, string $predictionDate, array $teamMappings)
    {
        foreach ($matches as $match) {
            if ($this->isMatch($match, $predictionMatch, $predictionDate, $teamMappings)) {
                $odds = $this->formatOdds($match);
                Log::info('Found matching odds', [
                    'prediction_match' => $predictionMatch,
                    'api_match' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'odds' => $odds,
                    'bookmakers' => count($match['bookmakers'] ?? [])
                ]);
                return $odds;
            }
        }

        Log::warning('No matching odds found', [
            'prediction_match' => $predictionMatch,
            'prediction_date' => $predictionDate,
            'available_matches' => array_map(function($match) {
                return [
                    'teams' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'date' => $match['commence_time'],
                    'bookmakers' => count($match['bookmakers'] ?? [])
                ];
            }, $matches)
        ]);

        return null;
    }

    /**
     * Check if API match matches our prediction
     */
    protected function isMatch(array $apiMatch, string $predictionMatch, string $predictionDate, array $teamMappings): bool
    {
        // Normalize team names for comparison
        $predictionTeams = $this->normalizeTeamNames($predictionMatch);
        $apiHomeTeam = $this->normalizeTeamName($apiMatch['home_team']);
        $apiAwayTeam = $this->normalizeTeamName($apiMatch['away_team']);

        // Check team mappings
        $homeTeamMatches = $this->checkTeamMapping($predictionTeams['home'], $apiHomeTeam, $teamMappings);
        $awayTeamMatches = $this->checkTeamMapping($predictionTeams['away'], $apiAwayTeam, $teamMappings);

        // Check if teams match (in any order)
        $teamsMatch = ($homeTeamMatches && $awayTeamMatches) || 
                     ($this->checkTeamMapping($predictionTeams['home'], $apiAwayTeam, $teamMappings) && 
                      $this->checkTeamMapping($predictionTeams['away'], $apiHomeTeam, $teamMappings));

        // Check if dates match (within 24 hours)
        $predictionDateTime = new \DateTime($predictionDate);
        $apiDateTime = new \DateTime($apiMatch['commence_time']);
        $dateDiff = abs($predictionDateTime->getTimestamp() - $apiDateTime->getTimestamp());
        $datesMatch = $dateDiff <= 86400; // 24 hours in seconds

        // Log the match result
        Log::info('Match comparison result', [
            'teams_match' => $teamsMatch,
            'dates_match' => $datesMatch,
            'date_diff_hours' => $dateDiff / 3600,
            'prediction_date' => $predictionDate,
            'api_date' => $apiMatch['commence_time'],
            'prediction_teams' => $predictionTeams,
            'api_teams' => [
                'home' => $apiHomeTeam,
                'away' => $apiAwayTeam
            ],
            'team_mappings' => [
                'home_match' => $homeTeamMatches,
                'away_match' => $awayTeamMatches
            ]
        ]);

        return $teamsMatch && $datesMatch;
    }

    /**
     * Check if a team matches using mappings
     */
    protected function checkTeamMapping(string $predictionTeam, string $apiTeam, array $teamMappings): bool
    {
        // Direct match
        if ($predictionTeam === $apiTeam) {
            return true;
        }

        // Check mappings
        foreach ($teamMappings as $key => $variations) {
            if ($predictionTeam === $key) {
                return in_array($apiTeam, $variations);
            }
        }

        // Fuzzy match as fallback
        return $this->fuzzyMatch($predictionTeam, $apiTeam);
    }

    /**
     * Fuzzy match team names
     */
    protected function fuzzyMatch(string $team1, string $team2): bool
    {
        $team1 = $this->normalizeTeamName($team1);
        $team2 = $this->normalizeTeamName($team2);
        
        // Remove common suffixes
        $suffixes = ['fc', 'fk', 'sk', 'sc', 'sv', 'tsv', 'vfb', 'vfl', 'ii', 'iii', 'iv', 'v', 'vi', 'vii', 'viii', 'ix', 'x'];
        foreach ($suffixes as $suffix) {
            $team1 = str_replace($suffix, '', $team1);
            $team2 = str_replace($suffix, '', $team2);
        }
        
        // Compare normalized names
        return trim($team1) === trim($team2);
    }

    /**
     * Format odds into our standard format
     */
    protected function formatOdds(array $match): array
    {
        $odds = [
            '1' => null,
            'X' => null,
            '2' => null
        ];

        // Get the first bookmaker's odds (usually the most reliable)
        if (!empty($match['bookmakers'])) {
            $bookmaker = $match['bookmakers'][0];
            foreach ($bookmaker['markets'] as $market) {
                if ($market['key'] === 'h2h') {
                    foreach ($market['outcomes'] as $outcome) {
                        if ($outcome['name'] === $match['home_team']) {
                            $odds['1'] = $outcome['price'];
                        } elseif ($outcome['name'] === $match['away_team']) {
                            $odds['2'] = $outcome['price'];
                        }
                    }
                }
            }
        }

        // Calculate draw odds if we have both home and away odds
        if ($odds['1'] && $odds['2']) {
            $odds['X'] = round(1 / (1 - (1/$odds['1']) - (1/$odds['2'])), 2);
        }

        return $odds;
    }

    /**
     * Normalize team names for comparison
     */
    protected function normalizeTeamNames(string $matchString): array
    {
        $teams = explode(' vs ', $matchString);
        return [
            'home' => $this->normalizeTeamName($teams[0]),
            'away' => $this->normalizeTeamName($teams[1] ?? '')
        ];
    }

    /**
     * Normalize a single team name
     */
    protected function normalizeTeamName(string $team): string
    {
        // Remove common suffixes and prefixes
        $team = preg_replace('/^(FC|FK|SK|SC|SV|TSV|VfB|VfL|1\.|2\.|3\.|4\.|5\.|6\.|7\.|8\.|9\.|10\.)\s*/i', '', $team);
        $team = preg_replace('/\s+(FC|FK|SK|SC|SV|TSV|VfB|VfL|II|III|IV|V|VI|VII|VIII|IX|X)$/i', '', $team);
        
        // Convert to lowercase and trim
        return strtolower(trim($team));
    }
} 