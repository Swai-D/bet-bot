<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraperService;
use App\Services\OddsApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AutoScraperCommand extends Command
{
    protected $signature = 'scraper:auto';
    protected $description = 'Run automatic scraping based on schedule';

    private $adibetScraper;
    private $oddsApiService;

    public function __construct(
        AdibetScraperService $adibetScraper,
        OddsApiService $oddsApiService
    ) {
        parent::__construct();
        $this->adibetScraper = $adibetScraper;
        $this->oddsApiService = $oddsApiService;
    }

    public function handle()
    {
        try {
            $this->info('Starting auto scraping...');

            // Check if auto scraping is enabled
            if (!Cache::get('auto_scraping_enabled', false)) {
                $this->info('Auto scraping is disabled');
                return;
            }

            // Get scheduled time
            $scheduledTime = Cache::get('scraper_scheduled_time', '09:00');
            $currentTime = now()->format('H:i');

            if ($currentTime !== $scheduledTime) {
                $this->info('Not time for scraping yet');
                return;
            }

            // Run scraper
            $matches = $this->adibetScraper->scrapeMatches();
            
            if (empty($matches)) {
                $this->error('No matches found');
                return;
            }

            // Process matches
            foreach ($matches as $match) {
                // Get odds
                $odds = $this->oddsApiService->getMatchOdds(
                    $match['home_team'],
                    $match['away_team']
                );

                // Calculate match score
                $matchScore = $this->adibetScraper->calculateMatchScore($match);

                // Store match data
                Cache::put('match_' . $match['id'], [
                    'match' => $match,
                    'odds' => $odds,
                    'score' => $matchScore,
                    'scraped_at' => now()
                ], now()->addDay());

                $this->info("Scraped match: {$match['home_team']} vs {$match['away_team']}");
            }

            // Update last run time
            Cache::put('last_scraper_run', now(), now()->addDay());

            $this->info('Auto scraping completed successfully');

        } catch (\Exception $e) {
            Log::error('Auto scraping failed: ' . $e->getMessage());
            $this->error('Auto scraping failed: ' . $e->getMessage());
        }
    }
} 