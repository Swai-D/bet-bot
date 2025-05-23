<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OddsPortalScraper;
use App\Models\Prediction;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class TestOddsPortalCommand extends Command
{
    protected $signature = 'test:oddsportal 
        {--date= : Filter predictions by date (format: Y-m-d)}
        {--match= : Filter predictions by specific match}
        {--clear-cache : Clear the cache before fetching odds}
        {--force : Force update even for past matches}';

    protected $description = 'Test the OddsPortal scraper with predictions from the database';

    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                ODDS PORTAL SCRAPER TEST                     ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Build query
        $query = Prediction::whereNotNull('match')
            ->whereNotNull('tips');

        // Add date filter if provided
        if ($date = $this->option('date')) {
            $query->whereDate('date', $date);
            $this->info("📅 Filtering predictions for date: {$date}");
        }

        // Add match filter if provided
        if ($match = $this->option('match')) {
            $query->where('match', 'like', "%{$match}%");
            $this->info("🏆 Filtering predictions for match: {$match}");
        }

        $predictions = $query->get();

        if ($predictions->isEmpty()) {
            $this->error('No predictions found in the database.');
            return;
        }

        $this->info('📋 Found ' . $predictions->count() . ' predictions');
        $this->newLine();

        // Check for past matches
        $now = Carbon::now();
        $pastMatches = $predictions->filter(function ($prediction) use ($now) {
            return Carbon::parse($prediction->date)->isPast();
        });

        if ($pastMatches->isNotEmpty() && !$this->option('force')) {
            $this->warn('⚠️  Found ' . $pastMatches->count() . ' past matches!');
            $this->warn('    These matches might not have odds available on OddsPortal.');
            
            if (!$this->confirm('Do you want to continue with past matches?')) {
                $this->info('Operation cancelled.');
                return;
            }
        }

        // Clear cache if requested
        if ($this->option('clear-cache')) {
            $this->info('Clearing cache...');
            foreach ($predictions as $prediction) {
                $teams = (new OddsPortalScraper)->parseMatchString($prediction->match);
                if ($teams) {
                    foreach ($prediction->tips as $tip) {
                        $cacheKey = "oddsportal:{$teams['home']}:{$teams['away']}:{$tip['option']}";
                        Cache::forget($cacheKey);
                        $this->line("Cache cleared for key: {$cacheKey}");
                    }
                }
            }
            $this->newLine();
        }

        $this->info('Fetching odds from OddsPortal...');
        $this->newLine();

        $startTime = microtime(true);
        $scraper = new OddsPortalScraper();

        $results = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($predictions as $prediction) {
            $matchDate = Carbon::parse($prediction->date)->format('Y-m-d H:i');
            $this->line("Processing match: {$prediction->match} ({$matchDate})");
            
            foreach ($prediction->tips as $tip) {
                $this->line("  - Getting odds for tip: {$tip['option']}");
                
                $odds = $scraper->getOdds($prediction->match, $tip['option']);
                
                if ($odds) {
                    // Update the tip with the new odds
                    $tip['odd'] = $odds['value'];
                    $tip['bookmaker'] = $odds['bookmaker'];
                    
                    $results[] = [
                        'match' => $prediction->match,
                        'date' => $matchDate,
                        'tip' => $tip['option'],
                        'odd' => $odds['value'],
                        'bookmaker' => $odds['bookmaker'],
                        'status' => '✅ Success'
                    ];
                    $successCount++;
                } else {
                    $results[] = [
                        'match' => $prediction->match,
                        'date' => $matchDate,
                        'tip' => $tip['option'],
                        'odd' => 'N/A',
                        'bookmaker' => 'N/A',
                        'status' => '❌ Failed'
                    ];
                    $failedCount++;
                }
            }

            // Update the prediction with new odds
            $prediction->save();
        }

        $duration = round(microtime(true) - $startTime, 2);

        $this->info('✅ Test Results:');
        $this->line('----------------------------------------');

        $this->table(
            ['Match', 'Date', 'Tip', 'Odd', 'Bookmaker', 'Status'],
            $results
        );

        $this->newLine();
        $this->info("Summary:");
        $this->line("- Total matches processed: " . $predictions->count());
        $this->line("- Successful odds fetched: " . $successCount);
        $this->line("- Failed odds fetches: " . $failedCount);
        $this->line("- Duration: {$duration} seconds");
    }
} 