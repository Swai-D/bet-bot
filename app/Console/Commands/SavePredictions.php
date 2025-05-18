<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraper;
use Illuminate\Support\Facades\Log;

class SavePredictions extends Command
{
    protected $signature = 'predictions:save';
    protected $description = 'Fetch and save predictions from Adibet';

    public function handle()
    {
        $this->info('Starting to fetch and save predictions from Adibet...');
        Log::info('Starting predictions fetch and save process');
        
        try {
            $scraper = new AdibetScraper();
            
            // Fetch predictions
            $this->info('Fetching predictions...');
            $predictions = $scraper->fetchPredictions();
            
            if (empty($predictions)) {
                $this->error('No predictions found!');
                Log::warning('No predictions found from Adibet');
                return Command::FAILURE;
            }
            
            $this->info('Found ' . count($predictions) . ' predictions');
            Log::info('Fetched predictions', ['count' => count($predictions)]);
            
            // Save predictions
            $this->info('Saving predictions to database...');
            $result = $scraper->savePredictions($predictions);
            
            $this->info('Prediction saving completed:');
            $this->info('- Saved: ' . $result['saved']);
            $this->info('- Skipped: ' . $result['skipped']);
            $this->info('- Errors: ' . $result['errors']);
            
            Log::info('Predictions saved successfully', $result);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $errorMessage = 'Error: ' . $e->getMessage();
            $this->error($errorMessage);
            Log::error($errorMessage, [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
} 