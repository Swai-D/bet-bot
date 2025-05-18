<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraper;
use Carbon\Carbon;

class ImportHistoricalPredictions extends Command
{
    protected $signature = 'predictions:import-historical {--days=7}';
    protected $description = 'Import historical predictions from Adibet';

    public function handle()
    {
        $scraper = new AdibetScraper();
        $days = $this->option('days');
        
        $this->info("Starting to import historical predictions for the last {$days} days...");
        
        try {
            // Fetch predictions
            $predictions = $scraper->fetchPredictions();
            
            if (empty($predictions)) {
                $this->error('No predictions found!');
                return 1;
            }
            
            $this->info('Found ' . count($predictions) . ' predictions');
            
            // Save predictions
            $result = $scraper->savePredictions($predictions);
            
            $this->info('Import completed:');
            $this->info('- Saved: ' . $result['saved']);
            $this->info('- Skipped: ' . $result['skipped']);
            $this->info('- Errors: ' . $result['errors']);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Failed to import predictions: ' . $e->getMessage());
            return 1;
        }
    }
} 