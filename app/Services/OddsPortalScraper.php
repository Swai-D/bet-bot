<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class OddsPortalScraper
{
    protected $tipTypeMap = [
        '1' => '1X2',
        'X' => '1X2',
        '2' => '1X2',
        '+2.5' => 'Over/Under',
        '-2.5' => 'Over/Under',
        'GG' => 'Both Teams to Score',
        'NG' => 'Both Teams to Score',
        '1X' => 'Double Chance',
        '12' => 'Double Chance',
        'X2' => 'Double Chance',
        'AH' => 'Asian Handicap'
    ];

    public function getOdds(string $match, string $tipType)
    {
        $teams = $this->parseMatchString($match);
        if (!$teams) {
            return null;
        }

        $cacheKey = "oddsportal:{$teams['home']}:{$teams['away']}:{$tipType}";
        
        return Cache::remember($cacheKey, now()->addMinutes(30), function () use ($teams, $tipType) {
            return $this->scrapeOdds($teams['home'], $teams['away'], $tipType);
        });
    }

    protected function scrapeOdds(string $homeTeam, string $awayTeam, string $tipType)
    {
        $scriptPath = base_path('scripts/oddsportal-scraper.js');
        
        if (!file_exists($scriptPath)) {
            Log::error('OddsPortal scraper script not found');
            return null;
        }

        $process = new Process([
            'node',
            $scriptPath,
            json_encode([
                'homeTeam' => $homeTeam,
                'awayTeam' => $awayTeam,
                'tipType' => $tipType
            ])
        ]);

        try {
            $process->run();
            
            if (!$process->isSuccessful()) {
                Log::error('OddsPortal scraper failed: ' . $process->getErrorOutput());
                return null;
            }

            $result = json_decode($process->getOutput(), true);
            
            if (!$result || empty($result)) {
                Log::error('No odds data returned from OddsPortal scraper');
                return null;
            }

            return $this->getBestOdd($result, $tipType);

        } catch (\Exception $e) {
            Log::error('Error running OddsPortal scraper: ' . $e->getMessage());
            return null;
        }
    }

    protected function getBestOdd(array $oddsData, string $tipType)
    {
        $bestOdd = null;
        $highestValue = 0;

        foreach ($oddsData as $bookmaker) {
            if (!isset($bookmaker['odds']) || empty($bookmaker['odds'])) {
                continue;
            }

            $oddValue = $this->getOddValueForTipType($bookmaker['odds'], $tipType);
            
            if ($oddValue && $oddValue > $highestValue) {
                $highestValue = $oddValue;
                $bestOdd = [
                    'value' => $oddValue,
                    'bookmaker' => $bookmaker['bookmaker']
                ];
            }
        }

        return $bestOdd;
    }

    protected function getOddValueForTipType(array $odds, string $tipType)
    {
        if (in_array($tipType, ['1', 'X', '2'])) {
            $index = $tipType === '1' ? 0 : ($tipType === 'X' ? 1 : 2);
            return isset($odds[$index]) ? (float) $odds[$index] : null;
        }
        return null;
    }

    public function parseMatchString(string $matchString)
    {
        $parts = explode(' vs ', $matchString);
        if (count($parts) !== 2) {
            return null;
        }

        return [
            'home' => trim($parts[0]),
            'away' => trim($parts[1])
        ];
    }
} 
 