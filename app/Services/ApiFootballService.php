<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ApiFootballService
{
    protected $apiKey;
    protected $baseUrl = 'https://v3.football.api-sports.io';
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

    /**
     * Get odds for a specific match
     */
    public function getMatchOdds(string $match, string $date, string $league = 'england')
    {
        try {
            if (!isset($this->leagueMap[$league])) {
                Log::error('Invalid league specified', ['league' => $league]);
                return null;
            }

            $leagueInfo = $this->leagueMap[$league];
            $apiLeague = $leagueInfo['api_league'];
            $apiSeason = $leagueInfo['api_season'];

            // Split match string into home and away teams
            $teams = explode(' vs ', $match);
            if (count($teams) !== 2) {
                Log::error('Invalid match format', ['match' => $match]);
                return null;
            }

            $homeTeam = trim($teams[0]);
            $awayTeam = trim($teams[1]);

            // Normalize team names using the mapping
            $homeTeam = $leagueInfo['teams'][$homeTeam] ?? $homeTeam;
            $awayTeam = $leagueInfo['teams'][$awayTeam] ?? $awayTeam;

            // Convert date to EAT timezone
            $dateObj = Carbon::parse($date)->setTimezone($this->timezone);
            $formattedDate = $dateObj->format('Y-m-d');

            // Log request details
            Log::info('Fetching odds', [
                'match' => $match,
                'home_team' => $homeTeam,
                'away_team' => $awayTeam,
                'date' => $formattedDate,
                'league' => $apiLeague,
                'season' => $apiSeason
            ]);

            // First get fixtures for the date
            $fixturesResponse = Http::withHeaders([
                'x-rapidapi-key' => $this->apiKey,
                'x-rapidapi-host' => 'v3.football.api-sports.io'
            ])->get("{$this->baseUrl}/fixtures", [
                'league' => $apiLeague,
                'season' => $apiSeason,
                'date' => $formattedDate
            ]);

            if (!$fixturesResponse->successful()) {
                Log::error('Fixtures API request failed', [
                    'status' => $fixturesResponse->status(),
                    'response' => $fixturesResponse->json()
                ]);
                return null;
            }

            $fixtures = $fixturesResponse->json();
            
            if (!isset($fixtures['response']) || empty($fixtures['response'])) {
                Log::warning('No fixtures found', [
                    'league' => $apiLeague,
                    'date' => $formattedDate,
                    'response' => $fixtures
                ]);
                return null;
            }

            // Find the specific match
            $targetFixture = null;
            foreach ($fixtures['response'] as $fixture) {
                $fixtureHomeTeam = $fixture['teams']['home']['name'];
                $fixtureAwayTeam = $fixture['teams']['away']['name'];

                if ($this->isTeamMatch($fixtureHomeTeam, $homeTeam) && 
                    $this->isTeamMatch($fixtureAwayTeam, $awayTeam)) {
                    $targetFixture = $fixture;
                    break;
                }
            }

            if (!$targetFixture) {
                Log::warning('Match not found', [
                    'home' => $homeTeam,
                    'away' => $awayTeam,
                    'date' => $formattedDate
                ]);
                return null;
            }

            // Get odds for the specific fixture
            $oddsResponse = Http::withHeaders([
                'x-rapidapi-key' => $this->apiKey,
                'x-rapidapi-host' => 'v3.football.api-sports.io'
            ])->get("{$this->baseUrl}/odds", [
                'fixture' => $targetFixture['fixture']['id'],
                'bookmaker' => 8 // Bet365
            ]);

            if (!$oddsResponse->successful()) {
                Log::error('Odds API request failed', [
                    'status' => $oddsResponse->status(),
                    'response' => $oddsResponse->json()
                ]);
                return null;
            }

            $oddsData = $oddsResponse->json();

            if (!isset($oddsData['response']) || empty($oddsData['response'])) {
                Log::warning('No odds found', [
                    'fixture' => $targetFixture['fixture']['id'],
                    'response' => $oddsData
                ]);
                return null;
            }

            // Extract match odds from Bet365
            $bet365Odds = null;
            foreach ($oddsData['response'][0]['bookmakers'] as $bookmaker) {
                if ($bookmaker['id'] === 8) { // Bet365
                    foreach ($bookmaker['bets'] as $bet) {
                        if ($bet['id'] === 1) { // Match Winner
                            $bet365Odds = $bet['values'];
                            break 2;
                        }
                    }
                }
            }

            if (!$bet365Odds) {
                Log::warning('No Bet365 odds found', [
                    'fixture' => $targetFixture['fixture']['id'],
                    'response' => $oddsData
                ]);
                return null;
            }

            // Format odds
            $formattedOdds = [];
            foreach ($bet365Odds as $odd) {
                switch ($odd['value']) {
                    case 'Home':
                        $formattedOdds['1'] = floatval($odd['odd']);
                        break;
                    case 'Draw':
                        $formattedOdds['X'] = floatval($odd['odd']);
                        break;
                    case 'Away':
                        $formattedOdds['2'] = floatval($odd['odd']);
                        break;
                }
            }

            // Log the odds
            Log::info('Odds found', [
                'match' => $match,
                'fixture_id' => $targetFixture['fixture']['id'],
                'odds' => $formattedOdds
            ]);

            return $formattedOdds;

        } catch (\Exception $e) {
            Log::error('Error fetching match odds', [
                'error' => $e->getMessage(),
                'match' => $match,
                'date' => $date,
                'league' => $league
            ]);
            return null;
        }
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