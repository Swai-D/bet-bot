<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class AdibetScraperService
{
    private $baseUrl = 'https://adibet.com';
    private $puppeteer;

    public function __construct()
    {
        $this->initPuppeteer();
    }

    /**
     * Initialize Puppeteer browser
     */
    private function initPuppeteer()
    {
        try {
            $this->puppeteer = new \Nesk\Puphpeteer\Puppeteer;
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to initialize Puppeteer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Scrape matches from Adibet
     */
    public function scrapeMatches()
    {
        if (!$this->puppeteer) {
            return [];
        }

        try {
            $browser = $this->puppeteer->launch([
                'headless' => true,
                'args' => ['--no-sandbox']
            ]);

            $page = $browser->newPage();
            
            // Navigate to predictions page
            $page->goto($this->baseUrl . '/predictions', ['waitUntil' => 'networkidle2']);

            // Wait for matches table to load
            $page->waitForSelector('.matches-table');

            // Extract matches data
            $matches = $page->evaluate(JsFunction::createWithBody("
                return Array.from(document.querySelectorAll('.match-row')).map(row => {
                    const dateCell = row.querySelector('.date-cell');
                    const matchCell = row.querySelector('.match-cell');
                    const tipsCell = row.querySelector('.tips-cell');
                    
                    return {
                        date: dateCell ? dateCell.textContent.trim() : '',
                        match: matchCell ? matchCell.textContent.trim() : '',
                        tips: tipsCell ? tipsCell.textContent.trim().split(',') : []
                    };
                });
            "));

            $browser->close();

            // Filter and process matches
            return $this->processMatches($matches);

        } catch (\Exception $e) {
            Log::error('Failed to scrape Adibet matches: ' . $e->getMessage());
            return [];
        }
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