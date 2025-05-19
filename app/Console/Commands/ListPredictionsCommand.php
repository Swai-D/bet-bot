<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use Illuminate\Console\Command;

class ListPredictionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:list {--date= : The date to list predictions for} {--country= : Filter by country} {--league= : Filter by league}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List predictions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');
        $country = $this->option('country');
        $league = $this->option('league');
        
        $this->info('Listing predictions...');
        
        try {
            $query = Prediction::query();
            
            if ($date) {
                $query->where('date', $date);
            }
            
            if ($country) {
                $query->where('country', $country);
            }
            
            if ($league) {
                $query->where('league', $league);
            }
            
            $predictions = $query->get();
            
            if ($predictions->isEmpty()) {
                $this->info('No predictions found');
                return Command::SUCCESS;
            }
            
            $headers = ['ID', 'Match', 'Country', 'League', 'Date', 'Tips (Odds/Status)'];
            
            $rows = $predictions->map(function ($prediction) {
                return [
                    $prediction->id,
                    $prediction->match,
                    $prediction->country,
                    $prediction->league,
                    $prediction->date,
                    collect($prediction->tips)->map(function ($tip) {
                        return $tip['option'] . ' (' . $tip['odd'] . '/' . $tip['status'] . ')';
                    })->join(', '),
                ];
            })->toArray();
            
            $this->table($headers, $rows);
            
            $this->info('Total: ' . count($predictions) . ' predictions');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to list predictions: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 