<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraper;
use Illuminate\Support\Facades\Log;

class TestScraper extends Command
{
    protected $signature = 'test:scraper {--date= : The target date in Y-m-d format}';
    protected $description = 'Test the Adibet scraper functionality';

    public function handle()
    {
        $this->info('Starting scraper test...');
        
        // Get target date from option or use today
        $targetDate = $this->option('date');
        
        // Initialize scraper with target date
        $scraper = new AdibetScraper(false, $targetDate);
        
        try {
            // Fetch predictions
            $this->info('Fetching predictions...');
            $predictions = $scraper->fetchPredictions();
            
            if (empty($predictions)) {
                $this->error('No predictions found!');
                return 1;
            }
            
            // Display results
            $this->info('Found ' . count($predictions) . ' predictions:');
            $this->newLine();
            
            foreach ($predictions as $prediction) {
                $this->info("Date: {$prediction['date']}");
                $this->info("Match: {$prediction['team_home']} vs {$prediction['team_away']}");
                $this->info("Country: {$prediction['country']}");
                $this->info("Tips:");
                
                foreach ($prediction['tips'] as $tip => $odds) {
                    $this->line("  - {$tip}: {$odds}");
                }
                
                $this->newLine();
            }
            
            // Save predictions to database
            $this->info('Saving predictions to database...');
            $scraper->savePredictions($predictions);
            
            // Filter good predictions
            $this->info('Filtering good predictions...');
            $goodPredictions = $scraper->filterGoodPredictions($predictions);
            
            if (!empty($goodPredictions)) {
                $this->info('Found ' . count($goodPredictions) . ' good predictions:');
                $this->newLine();
                
                foreach ($goodPredictions as $prediction) {
                    $this->info("Match: {$prediction['team_home']} vs {$prediction['team_away']}");
                    $this->info("Tips:");
                    
                    foreach ($prediction['tips'] as $tip => $odds) {
                        $this->line("  - {$tip}: {$odds}");
                    }
                    
                    $this->newLine();
                }
            } else {
                $this->warn('No good predictions found!');
            }
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Scraper test failed: ' . $e->getMessage());
            return 1;
        }
    }
} 