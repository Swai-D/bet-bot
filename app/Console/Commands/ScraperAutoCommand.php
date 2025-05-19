<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraperService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ScraperAutoCommand extends Command
{
    protected $signature = 'scraper:auto';
    protected $description = 'Run the scraper automatically';

    private $scraperService;

    public function __construct(AdibetScraperService $scraperService)
    {
        parent::__construct();
        $this->scraperService = $scraperService;
    }

    public function handle()
    {
        $this->info('Starting auto scraping...');

        // Check if scraper is enabled
        if (!config('automation.scraper.enabled')) {
            $this->error('Auto scraping is disabled');
            return;
        }

        try {
            // Get matches from scraper
            $matches = $this->scraperService->scrapeMatches();

            if (empty($matches)) {
                $this->warn('No matches found');
                return;
            }

            // Process and score matches
            $processedMatches = [];
            foreach ($matches as $match) {
                $score = $this->scraperService->calculateMatchScore($match);
                if ($score >= config('automation.betting.min_score')) {
                    $processedMatches[] = array_merge($match, ['score' => $score]);
                }
            }

            // Cache matches
            Cache::put('scraped_matches', $processedMatches, now()->addMinutes(config('automation.scraper.cache_time')));

            $this->info('Successfully scraped ' . count($processedMatches) . ' matches');
            Log::info('Auto scraper completed successfully', ['matches_count' => count($processedMatches)]);

        } catch (\Exception $e) {
            $this->error('Failed to run scraper: ' . $e->getMessage());
            Log::error('Auto scraper failed', ['error' => $e->getMessage()]);
        }
    }
} 