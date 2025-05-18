<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class OddsApiService
{
    private $baseUrl = 'https://api.the-odds-api.com/v4';
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.odds_api.key');
    }

    /**
     * Get odds for a specific match
     */
    public function getMatchOdds($homeTeam, $awayTeam)
    {
        try {
            // Try to get from cache first
            $cacheKey = "odds_{$homeTeam}_{$awayTeam}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Get all available matches
            $matches = $this->getAvailableMatches();
            
            // Find matching game
            foreach ($matches as $match) {
                if ($this->isMatchingGame($match, $homeTeam, $awayTeam)) {
                    $odds = $this->extractOdds($match);
                    Cache::put($cacheKey, $odds, now()->addMinutes(30));
                    return $odds;
                }
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to get match odds: ' . $e->getMessage());
            return null;
        }
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