<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class AdibetScraperService
{
    private $baseUrl = 'https://adibet.com';

    /**
     * Scrape matches from Adibet
     */
    public function scrapeMatches()
    {
        try {
            // Get predictions page
            $response = Http::get($this->baseUrl . '/predictions');
            
            if (!$response->successful()) {
                Log::error('Failed to fetch Adibet predictions page');
                return [];
            }

            // Extract matches using regex
            $html = $response->body();
            $matches = $this->extractMatches($html);

            // Filter and process matches
            return $this->processMatches($matches);

        } catch (\Exception $e) {
            Log::error('Failed to scrape Adibet matches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Extract matches from HTML
     */
    private function extractMatches($html)
    {
        $matches = [];
        $pattern = '/<tr class="match-row">(.*?)<\/tr>/s';
        
        if (preg_match_all($pattern, $html, $rows)) {
            foreach ($rows[1] as $row) {
                // Extract date
                preg_match('/<td class="date-cell">(.*?)<\/td>/s', $row, $date);
                
                // Extract match
                preg_match('/<td class="match-cell">(.*?)<\/td>/s', $row, $match);
                
                // Extract tips
                preg_match('/<td class="tips-cell">(.*?)<\/td>/s', $row, $tips);
                
                if ($date && $match && $tips) {
                    $matches[] = [
                        'date' => strip_tags($date[1]),
                        'match' => strip_tags($match[1]),
                        'tips' => array_map('trim', explode(',', strip_tags($tips[1])))
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Process and filter scraped matches
     */
    private function processMatches($matches)
    {
        $today = Carbon::today()->format('Y-m-d');
        $processedMatches = [];

        foreach ($matches as $match) {
            // Skip if no date or match info
            if (empty($match['date']) || empty($match['match'])) {
                continue;
            }

            // Parse date
            $matchDate = Carbon::parse($match['date'])->format('Y-m-d');
            
            // Skip if not today's match
            if ($matchDate !== $today) {
                continue;
            }

            // Extract teams and country
            preg_match('/^(.*?)\s+vs\s+(.*?)\s+\((.*?)\)$/', $match['match'], $parts);
            
            if (count($parts) !== 4) {
                continue;
            }

            $processedMatches[] = [
                'home_team' => trim($parts[1]),
                'away_team' => trim($parts[2]),
                'country' => trim($parts[3]),
                'date' => $matchDate,
                'tips' => array_map('trim', $match['tips'])
            ];
        }

        return $processedMatches;
    }

    /**
     * Get league score based on country
     */
    private function getLeagueScore($country)
    {
        $leagueScores = [
            'top' => [
                'England' => 5,
                'Germany' => 4,
                'Spain' => 4,
                'Italy' => 3,
                'France' => 3
            ],
            'moderate' => [
                'Netherlands' => 2,
                'Portugal' => 2,
                'Turkey' => 2,
                'Belgium' => 2,
                'Scotland' => 2
            ]
        ];

        foreach ($leagueScores['top'] as $league => $score) {
            if (stripos($country, $league) !== false) {
                return $score;
            }
        }

        foreach ($leagueScores['moderate'] as $league => $score) {
            if (stripos($country, $league) !== false) {
                return $score;
            }
        }

        return 1; // Default score for other leagues
    }

    /**
     * Check if match is important (derby/rivalry)
     */
    private function isImportantMatch($homeTeam, $awayTeam)
    {
        $importantMatches = [
            'Manchester' => ['United', 'City'],
            'Barcelona' => ['Real Madrid'],
            'Liverpool' => ['Everton'],
            'Milan' => ['Inter'],
            'Arsenal' => ['Tottenham']
        ];

        foreach ($importantMatches as $team => $rivals) {
            if (stripos($homeTeam, $team) !== false) {
                foreach ($rivals as $rival) {
                    if (stripos($awayTeam, $rival) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Calculate match score based on various factors
     */
    public function calculateMatchScore($match)
    {
        $score = 0;

        // Add league score
        $score += $this->getLeagueScore($match['country']);

        // Add importance score
        if ($this->isImportantMatch($match['home_team'], $match['away_team'])) {
            $score += 2;
        }

        // Add tips score
        $score += count($match['tips']) * 0.5;

        return $score;
    }
} 