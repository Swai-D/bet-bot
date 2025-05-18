<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraper;

class SavePredictions extends Command
{
    protected $signature = 'predictions:save';
    protected $description = 'Fetch and save predictions from Adibet';

    public function handle()
    {
        $this->info('Starting to fetch and save predictions from Adibet...');
        
        try {
            $scraper = new AdibetScraper();
            
            // Fetch predictions
            $this->info('Fetching predictions...');
            $predictions = $scraper->fetchPredictions();
            
            if (empty($predictions)) {
                $this->error('No predictions found!');
                return 1;
            }
            
            $this->info('Found ' . count($predictions) . ' predictions');
            
            // Save predictions
            $this->info('Saving predictions to database...');
            $result = $scraper->savePredictions($predictions);
            
            $this->info('Prediction saving completed:');
            $this->info('- Saved: ' . $result['saved']);
            $this->info('- Skipped: ' . $result['skipped']);
            $this->info('- Errors: ' . $result['errors']);
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
} 