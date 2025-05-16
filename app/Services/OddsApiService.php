<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OddsApiService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.the-odds-api.com/v4';
    
    // Map our leagues to API leagues
    protected $leagueMap = [
        'austria_2' => 'soccer_austria_bundesliga',
        'belgium' => 'soccer_belgium_first_div',
        'china' => 'soccer_china_superleague',
        'denmark' => 'soccer_denmark_superliga',
        'england' => 'soccer_epl',
        'germany' => 'soccer_germany_bundesliga',
        'spain' => 'soccer_spain_la_liga',
        'italy' => 'soccer_italy_serie_a',
        'france' => 'soccer_france_ligue_1',
        'netherlands' => 'soccer_netherlands_eredivisie',
        'portugal' => 'soccer_portugal_primeira_liga',
        'turkey' => 'soccer_turkey_super_league',
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
            $apiLeague = $this->mapLeague($league);
            
            // Log the request details
            Log::info('Fetching odds from API', [
                'match' => $match,
                'date' => $date,
                'league' => $league,
                'api_league' => $apiLeague,
                'api_key' => substr($this->apiKey, 0, 5) . '...' // Log partial key for debugging
            ]);

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
            
            // Validate API response
            if (!is_array($matches)) {
                Log::error('Invalid API Response', [
                    'response' => $matches,
                    'match' => $match,
                    'date' => $date,
                    'league' => $apiLeague
                ]);
                return null;
            }

            // Log the API response
            Log::info('API Response', [
                'match_count' => count($matches),
                'first_match' => $matches[0] ?? null,
                'league' => $apiLeague
            ]);

            if (empty($matches)) {
                Log::warning('No matches found in API response', [
                    'league' => $apiLeague,
                    'match' => $match,
                    'date' => $date
                ]);
                return null;
            }

            return $this->findMatchingOdds($matches, $match, $date);

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
    protected function mapLeague(string $league): string
    {
        $apiLeague = $this->leagueMap[$league] ?? 'soccer_epl';
        Log::info('League mapping', [
            'input_league' => $league,
            'api_league' => $apiLeague
        ]);
        return $apiLeague;
    }

    /**
     * Find matching odds for a prediction
     */
    protected function findMatchingOdds(array $matches, string $predictionMatch, string $predictionDate)
    {
        foreach ($matches as $match) {
            if ($this->isMatch($match, $predictionMatch, $predictionDate)) {
                $odds = $this->formatOdds($match);
                Log::info('Found matching odds', [
                    'prediction_match' => $predictionMatch,
                    'api_match' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'odds' => $odds
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
                    'date' => $match['commence_time']
                ];
            }, $matches)
        ]);

        return null;
    }

    /**
     * Check if API match matches our prediction
     */
    protected function isMatch(array $apiMatch, string $predictionMatch, string $predictionDate): bool
    {
        // Normalize team names for comparison
        $predictionTeams = $this->normalizeTeamNames($predictionMatch);
        $apiHomeTeam = $this->normalizeTeamName($apiMatch['home_team']);
        $apiAwayTeam = $this->normalizeTeamName($apiMatch['away_team']);

        // Log the comparison
        Log::info('Comparing teams', [
            'prediction' => $predictionTeams,
            'api' => [
                'home' => $apiHomeTeam,
                'away' => $apiAwayTeam
            ]
        ]);

        // Check if teams match (in any order)
        $teamsMatch = (
            ($predictionTeams['home'] === $apiHomeTeam && $predictionTeams['away'] === $apiAwayTeam) ||
            ($predictionTeams['home'] === $apiAwayTeam && $predictionTeams['away'] === $apiHomeTeam)
        );

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
            'api_date' => $apiMatch['commence_time']
        ]);

        return $teamsMatch && $datesMatch;
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

        // Log the formatted odds
        Log::info('Formatted odds', [
            'match' => $match['home_team'] . ' vs ' . $match['away_team'],
            'odds' => $odds,
            'bookmakers' => count($match['bookmakers'] ?? [])
        ]);

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