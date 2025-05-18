<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExportPredictionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:export {file : The path to save the JSON file} {--date= : The date to export predictions for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export predictions to a JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $date = $this->option('date');
        
        $this->info('Exporting predictions...');
        
        try {
            $query = Prediction::query();
            
            if ($date) {
                $query->where('date', $date);
            }
            
            $predictions = $query->get();
            
            if ($predictions->isEmpty()) {
                $this->info('No predictions found');
                return Command::SUCCESS;
            }
            
            $data = $predictions->map(function ($prediction) {
                return [
                    'match' => $prediction->match,
                    'country' => $prediction->country,
                    'league' => $prediction->league,
                    'date' => $prediction->date,
                    'tips' => $prediction->tips,
                ];
            })->toArray();
            
            file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
            
            $this->info('Export completed successfully');
            $this->info('Exported: ' . count($data) . ' predictions');
            $this->info('File saved to: ' . $file);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Export failed: ' . $e->getMessage());
            Log::error('Export failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 