<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OddsApiService
{
    protected $apiKey;
    protected $baseUrl = 'https://api.the-odds-api.com/v4/sports/soccer/odds';

    public function __construct()
    {
        $this->apiKey = config('services.odds_api.key');
    }

    public function getMatchOdds($match, $date, $option)
    {
        try {
            // Cache key based on match and date
            $cacheKey = 'odds_' . md5($match . $date);
            
            // Try to get from cache first
            if (Cache::has($cacheKey)) {
                $odds = Cache::get($cacheKey);
                return $this->getOddsForOption($odds, $option);
            }
            
            // If not in cache, fetch from API
            $response = Http::get($this->baseUrl, [
                'apiKey' => $this->apiKey,
                'regions' => 'eu',
                'markets' => 'h2h,totals',
                'dateFormat' => 'iso',
                'oddsFormat' => 'decimal'
            ]);

            if ($response->successful()) {
            $matches = $response->json();
            
                // Find matching game
                $game = $this->findMatchingGame($matches, $match, $date);
                
                if ($game) {
                    // Cache the odds for 1 hour
                    Cache::put($cacheKey, $game, 3600);
                    
                    return $this->getOddsForOption($game, $option);
                }
            }
            
            return 'N/A';

        } catch (\Exception $e) {
            Log::error('OddsAPI Error: ' . $e->getMessage(), [
                'match' => $match,
                'date' => $date,
                'option' => $option
            ]);
            return 'N/A';
        }
    }

    protected function findMatchingGame($matches, $searchMatch, $searchDate)
    {
        foreach ($matches as $match) {
            // Check if date matches
            $matchDate = date('Y-m-d', strtotime($match['commence_time']));
            $searchDate = date('Y-m-d', strtotime($searchDate));
            
            if ($matchDate !== $searchDate) {
                continue;
            }
            
            // Check if teams match
            $homeTeam = strtolower($match['home_team']);
            $awayTeam = strtolower($match['away_team']);
            $searchMatch = strtolower($searchMatch);
            
            if (strpos($searchMatch, $homeTeam) !== false && strpos($searchMatch, $awayTeam) !== false) {
                return $match;
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
        if (!$bookmaker || !isset($bookmaker['markets'])) {
            return 'N/A';
        }
        
        // Map our options to API markets
        $marketMap = [
            '1' => ['market' => 'h2h', 'index' => 0],
            'X' => ['market' => 'h2h', 'index' => 1],
            '2' => ['market' => 'h2h', 'index' => 2],
            'GG' => ['market' => 'totals', 'index' => 0],
            'NG' => ['market' => 'totals', 'index' => 1],
            '+2.5' => ['market' => 'totals', 'index' => 0],
            '-2.5' => ['market' => 'totals', 'index' => 1]
        ];
        
        $mapping = $marketMap[$option] ?? null;
        if (!$mapping) {
            return 'N/A';
        }
        
        // Find the market
        foreach ($bookmaker['markets'] as $market) {
            if ($market['key'] === $mapping['market']) {
                $odds = $market['outcomes'][$mapping['index']]['price'] ?? null;
                return $odds ? number_format($odds, 2) : 'N/A';
            }
        }
        
        return 'N/A';
    }

    /**
     * Get all available matches
     */
    private function getAvailableMatches()
    {
        try {
            $response = Http::get("{$this->baseUrl}/sports/soccer/odds", [
                'apiKey' => $this->apiKey,
                'regions' => 'eu',
                'markets' => 'h2h'
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch matches from Odds API', [
                'response' => $response->json()
            ]);
            return [];

        } catch (\Exception $e) {
            Log::error('Error fetching matches from Odds API: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if the match from API matches our teams
     */
    private function isMatchingGame($apiMatch, $homeTeam, $awayTeam)
    {
        $apiHomeTeam = strtolower($apiMatch['home_team']);
        $apiAwayTeam = strtolower($apiMatch['away_team']);
        
        $ourHomeTeam = strtolower($homeTeam);
        $ourAwayTeam = strtolower($awayTeam);

        // Exact match
        if ($apiHomeTeam === $ourHomeTeam && $apiAwayTeam === $ourAwayTeam) {
            return true;
        }

        // Partial match (handles team name variations)
        $homeTeamWords = explode(' ', $ourHomeTeam);
        $awayTeamWords = explode(' ', $ourAwayTeam);

        $homeMatch = false;
        $awayMatch = false;

        // Check if all words from our home team are in API home team
        foreach ($homeTeamWords as $word) {
            if (strlen($word) > 3 && strpos($apiHomeTeam, $word) !== false) {
                $homeMatch = true;
                break;
            }
        }

        // Check if all words from our away team are in API away team
        foreach ($awayTeamWords as $word) {
            if (strlen($word) > 3 && strpos($apiAwayTeam, $word) !== false) {
                $awayMatch = true;
                break;
            }
        }

        return $homeMatch && $awayMatch;
    }

    /**
     * Extract odds from match data
     */
    private function extractOdds($match)
    {
        try {
            $bookmakers = $match['bookmakers'] ?? [];
            $odds = [];

            foreach ($bookmakers as $bookmaker) {
                $markets = $bookmaker['markets'] ?? [];
                foreach ($markets as $market) {
                if ($market['key'] === 'h2h') {
                    foreach ($market['outcomes'] as $outcome) {
                            $odds[$outcome['name']] = $outcome['price'];
                        }
                    }
                }
            }

            return [
                'home' => $odds['home'] ?? null,
                'draw' => $odds['draw'] ?? null,
                'away' => $odds['away'] ?? null
            ];

        } catch (\Exception $e) {
            Log::error('Failed to extract odds: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if odds are favorable for betting
     */
    public function areOddsFavorable($odds, $minOdds = 2.0)
    {
        if (!$odds) {
            return false;
        }

        // Check if any odds are above minimum threshold
        foreach ($odds as $odd) {
            if ($odd && $odd >= $minOdds) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get remaining API requests
     */
    public function getRemainingRequests()
    {
        try {
            $response = Http::get("{$this->baseUrl}/sports", [
                'apiKey' => $this->apiKey
            ]);

            if ($response->successful()) {
                return $response->header('x-requests-remaining');
            }

            return 0;

        } catch (\Exception $e) {
            Log::error('Failed to get remaining requests: ' . $e->getMessage());
            return 0;
        }
    }
} 