<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ApiFootballService
{
    protected $apiKey;
    protected $baseUrl = 'https://api-football-v1.p.rapidapi.com/v3';
    protected $timezone = 'Africa/Dar_es_Salaam'; // EAT timezone
    
    // Map our leagues to API leagues with proper team mappings
    protected $leagueMap = [
        'austria_2' => [
            'api_league' => 218, // Austria 2. Liga
            'api_season' => 2023,
            'teams' => [
                'St. Polten' => 'St. Pölten',
                'SK Rapid II' => 'Rapid Wien II',
                'Bregenz' => 'SC Bregenz',
                'Ried' => 'SV Ried'
            ]
        ],
        'belgium' => [
            'api_league' => 144, // Belgium First Division A
            'api_season' => 2023,
            'teams' => [
                'Leuven' => 'OH Leuven',
                'Westerlo' => 'Westerlo'
            ]
        ],
        'china' => [
            'api_league' => 169, // Chinese Super League
            'api_season' => 2023,
            'teams' => [
                'Yunnan Yukun' => 'Yunnan Yukun',
                'Meizhou Hakka' => 'Meizhou Hakka'
            ]
        ],
        'denmark' => [
            'api_league' => 119, // Denmark Superliga
            'api_season' => 2023,
            'teams' => [
                'Norsjaelland' => 'Nordsjælland',
                'Aarhus' => 'Aarhus GF'
            ]
        ],
        'england' => [
            'api_league' => 39, // Premier League
            'teams' => []
        ],
        'germany' => [
            'api_league' => 78, // Bundesliga
            'teams' => []
        ],
        'spain' => [
            'api_league' => 140, // La Liga
            'teams' => []
        ],
        'italy' => [
            'api_league' => 135, // Serie A
            'teams' => []
        ],
        'france' => [
            'api_league' => 61, // Ligue 1
            'teams' => []
        ],
        'netherlands' => [
            'api_league' => 88, // Eredivisie
            'teams' => []
        ],
        'portugal' => [
            'api_league' => 94, // Primeira Liga
            'teams' => []
        ],
        'turkey' => [
            'api_league' => 203, // Super Lig
            'teams' => []
        ],
    ];

    public function __construct()
    {
        $this->apiKey = config('services.api_football.key');
        if (empty($this->apiKey)) {
            Log::error('API-FOOTBALL key is not configured');
            throw new \RuntimeException('API-FOOTBALL key is not configured');
        }
    }

    public function getMatchOdds($match, $date, $option)
    {
        try {
            // Cache key based on match and date
            $cacheKey = 'api_football_odds_' . md5($match . $date);
            
            // Try to get from cache first
            if (Cache::has($cacheKey)) {
                $odds = Cache::get($cacheKey);
                return $this->getOddsForOption($odds, $option);
            }
            
            // If not in cache, fetch from API
            $response = Http::withHeaders([
                'x-rapidapi-host' => 'api-football-v1.p.rapidapi.com',
                'x-rapidapi-key' => $this->apiKey
            ])->get($this->baseUrl . '/fixtures', [
                'date' => date('Y-m-d', strtotime($date)),
                'league' => $this->getLeagueId($match),
                'season' => date('Y')
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $fixtures = $data['response'] ?? [];
                
                // Find matching game
                $game = $this->findMatchingGame($fixtures, $match);
                
                if ($game) {
                    // Get odds for this fixture
                    $oddsResponse = Http::withHeaders([
                        'x-rapidapi-host' => 'api-football-v1.p.rapidapi.com',
                        'x-rapidapi-key' => $this->apiKey
                    ])->get($this->baseUrl . '/odds', [
                        'fixture' => $game['fixture']['id']
                    ]);
                    
                    if ($oddsResponse->successful()) {
                        $oddsData = $oddsResponse->json();
                        $odds = $oddsData['response'][0] ?? null;
                        
                        if ($odds) {
                            // Cache the odds for 1 hour
                            Cache::put($cacheKey, $odds, 3600);
                            
                            return $this->getOddsForOption($odds, $option);
                        }
                    }
                }
            }
            
            return 'N/A';
            
        } catch (\Exception $e) {
            Log::error('API-Football Error: ' . $e->getMessage(), [
                'match' => $match,
                'date' => $date,
                'option' => $option
            ]);
            return 'N/A';
        }
    }

    protected function findMatchingGame($fixtures, $searchMatch)
    {
        foreach ($fixtures as $fixture) {
            $homeTeam = strtolower($fixture['teams']['home']['name']);
            $awayTeam = strtolower($fixture['teams']['away']['name']);
            $searchMatch = strtolower($searchMatch);
            
            if (strpos($searchMatch, $homeTeam) !== false && strpos($searchMatch, $awayTeam) !== false) {
                return $fixture;
            }
        }
        
        return null;
    }

    protected function getOddsForOption($game, $option)
    {
        if (!$game || !isset($game['bookmakers'])) {
            return 'N/A';
        }
        
        // Get first bookmaker (usually most reliable)
        $bookmaker = $game['bookmakers'][0] ?? null;
        if (!$bookmaker || !isset($bookmaker['bets'])) {
            return 'N/A';
        }
        
        // Map our options to API markets
        $marketMap = [
            '1' => ['market' => 'Match Winner', 'value' => 'Home'],
            'X' => ['market' => 'Match Winner', 'value' => 'Draw'],
            '2' => ['market' => 'Match Winner', 'value' => 'Away'],
            'GG' => ['market' => 'Both Teams Score', 'value' => 'Yes'],
            'NG' => ['market' => 'Both Teams Score', 'value' => 'No'],
            '+2.5' => ['market' => 'Total Goals', 'value' => 'Over 2.5'],
            '-2.5' => ['market' => 'Total Goals', 'value' => 'Under 2.5']
        ];
        
        $mapping = $marketMap[$option] ?? null;
        if (!$mapping) {
            return 'N/A';
        }
        
        // Find the market
        foreach ($bookmaker['bets'] as $bet) {
            if ($bet['name'] === $mapping['market']) {
                foreach ($bet['values'] as $value) {
                    if ($value['value'] === $mapping['value']) {
                        return number_format($value['odd'], 2);
                    }
                }
            }
        }
        
        return 'N/A';
    }

    protected function getLeagueId($match)
    {
        // Map common leagues to their API-Football IDs
        $leagueMap = [
            'Premier League' => 39,
            'La Liga' => 140,
            'Serie A' => 135,
            'Bundesliga' => 78,
            'Ligue 1' => 61,
            'Eredivisie' => 88,
            'Primeira Liga' => 94,
            'Super Lig' => 203
        ];
        
        foreach ($leagueMap as $league => $id) {
            if (stripos($match, $league) !== false) {
                return $id;
            }
        }
        
        return null;
    }

    /**
     * Check if API fixture matches our prediction
     */
    protected function isMatch(array $fixture, array $predictionTeams, array $teamMappings): bool
    {
        $apiHomeTeam = $this->normalizeTeamName($fixture['teams']['home']['name']);
        $apiAwayTeam = $this->normalizeTeamName($fixture['teams']['away']['name']);

        // Check team mappings
        $homeTeamMatches = $this->checkTeamMapping($predictionTeams['home'], $apiHomeTeam, $teamMappings);
        $awayTeamMatches = $this->checkTeamMapping($predictionTeams['away'], $apiAwayTeam, $teamMappings);

        // Check if teams match (in any order)
        $teamsMatch = ($homeTeamMatches && $awayTeamMatches) || 
                     ($this->checkTeamMapping($predictionTeams['home'], $apiAwayTeam, $teamMappings) && 
                      $this->checkTeamMapping($predictionTeams['away'], $apiHomeTeam, $teamMappings));

        // Log the match result
        Log::info('Fixture comparison result', [
            'teams_match' => $teamsMatch,
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

        return $teamsMatch;
    }

    /**
     * Format odds into our standard format
     */
    protected function formatOdds(array $oddsData): array
    {
        $odds = [
            '1' => null,
            'X' => null,
            '2' => null
        ];

        if (isset($oddsData['bookmakers'][0]['bets'][0]['values'])) {
            foreach ($oddsData['bookmakers'][0]['bets'][0]['values'] as $value) {
                switch ($value['value']) {
                    case 'Home':
                        $odds['1'] = floatval($value['odd']);
                        break;
                    case 'Draw':
                        $odds['X'] = floatval($value['odd']);
                        break;
                    case 'Away':
                        $odds['2'] = floatval($value['odd']);
                        break;
                }
            }
        }

        return $odds;
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

    protected function isTeamMatch($apiTeam, $ourTeam)
    {
        // Convert both to lowercase for comparison
        $apiTeam = strtolower($apiTeam);
        $ourTeam = strtolower($ourTeam);

        // Remove common suffixes and special characters
        $apiTeam = preg_replace('/[^a-z0-9]/', '', $apiTeam);
        $ourTeam = preg_replace('/[^a-z0-9]/', '', $ourTeam);

        // Check if one contains the other
        return strpos($apiTeam, $ourTeam) !== false || strpos($ourTeam, $apiTeam) !== false;
    }
} 