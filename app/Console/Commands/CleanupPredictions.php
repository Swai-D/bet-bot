<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraper;

class CleanupPredictions extends Command
{
    protected $signature = 'predictions:cleanup {--days=7 : Number of days to keep predictions}';
    protected $description = 'Clean up old predictions that are no longer relevant';

    public function handle()
    {
        $this->info('Starting predictions cleanup...');
        
        $daysToKeep = $this->option('days');
        $scraper = new AdibetScraper();
        
        $deletedCount = $scraper->cleanupOldPredictions($daysToKeep);
        
        $this->info("Successfully cleaned up {$deletedCount} old predictions");
        
        return 0;
    }
} 