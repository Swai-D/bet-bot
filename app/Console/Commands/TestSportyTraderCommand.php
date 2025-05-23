<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Services\SportyTraderScraper;

class TestSportyTraderCommand extends Command
{
    protected $signature = 'prediction:test-sportytrader
                            {--save : Save predictions to database}
                            {--odds : Fetch odds for predictions}
                            {--detailed : Show detailed view}';

    protected $description = 'Test SportyTrader scraper and optionally save predictions';

    public function handle()
    {
        $this->showHeader();

        try {
            $this->info('🚀 Starting SportyTrader scraper test...');
            
            $scraper = new SportyTraderScraper();
            $result = $scraper->getPredictionsWithOdds();

            if (!$result['success']) {
                $this->error('❌ Failed to fetch predictions: ' . $result['error']);
                return 1;
            }

            $predictions = $result['data'];
            
            if (empty($predictions)) {
                $this->warn('⚠️ No predictions found');
                return 0;
            }

            $this->info('✅ Successfully fetched ' . count($predictions) . ' predictions');
            
            if ($this->option('detailed')) {
                $this->showDetailedView($predictions);
            } else {
                $this->showCompactView($predictions);
            }

            if ($this->option('save')) {
                $this->savePredictions($predictions);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            Log::error('SportyTrader scraper error: ' . $e->getMessage());
            return 1;
        }
    }

    protected function showHeader()
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                SPORTYTRADER SCRAPER TEST                    ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showCompactView($predictions)
    {
        $this->newLine();
        $this->info('📊 PREDICTIONS TABLE');
        $this->newLine();

        $headers = ['Match', 'Date', 'Country', 'League', 'Prediction', 'Odds'];
        $rows = collect($predictions)->map(function ($prediction) {
            return [
                'match' => $prediction['match'],
                'date' => $prediction['date'] . ' ' . $prediction['time'],
                'country' => $prediction['country'],
                'league' => $prediction['league'],
                'prediction' => $prediction['tips'][0]['prediction'] ?? 'N/A',
                'odds' => $prediction['tips'][0]['odds'] ?? 'N/A'
            ];
        })->toArray();

        $this->table($headers, $rows);
    }

    protected function showDetailedView($predictions)
    {
        $this->newLine();
        $this->info('📋 DETAILED PREDICTIONS');
        $this->newLine();

        foreach ($predictions as $prediction) {
            $this->showPredictionDetails($prediction);
        }
    }

    protected function showPredictionDetails($prediction)
    {
        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║ ' . str_pad($prediction['match'], 58) . ' ║');
        $this->line('╠════════════════════════════════════════════════════════════╣');
        $this->line('║ Date: ' . str_pad($prediction['date'] . ' ' . $prediction['time'], 52) . ' ║');
        $this->line('║ Country: ' . str_pad($prediction['country'], 50) . ' ║');
        $this->line('║ League: ' . str_pad($prediction['league'], 51) . ' ║');
        
        foreach ($prediction['tips'] as $tip) {
            $this->line('║ Prediction: ' . str_pad($tip['prediction'], 47) . ' ║');
            $this->line('║ Odds: ' . str_pad($tip['odds'], 53) . ' ║');
        }
        
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function savePredictions($predictions)
    {
        $this->info('💾 Saving predictions to database...');
        
        $saved = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($predictions as $prediction) {
            try {
                $response = app()->make('App\Http\Controllers\PredictionController')
                    ->store(new \Illuminate\Http\Request($prediction));
                
                if ($response->getStatusCode() === 200) {
                    $saved++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('Error saving prediction: ' . $e->getMessage());
            }
        }

        $this->info("✅ Saved: {$saved}, Skipped: {$skipped}, Errors: {$errors}");
    }
} 