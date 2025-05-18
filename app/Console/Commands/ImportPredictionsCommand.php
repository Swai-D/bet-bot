<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ImportPredictionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:import {file : The path to the JSON file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import predictions from a JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        
        if (!file_exists($file)) {
            $this->error('File not found: ' . $file);
            return Command::FAILURE;
        }
        
        $this->info('Importing predictions from: ' . $file);
        
        try {
            $data = json_decode(file_get_contents($file), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON file');
                return Command::FAILURE;
            }
            
            $imported = 0;
            $skipped = 0;
            $errors = 0;
            
            foreach ($data as $item) {
                try {
                    // Check if prediction already exists
                    $exists = Prediction::where('match', $item['match'])
                        ->where('date', $item['date'])
                        ->exists();
                    
                    if ($exists) {
                        $skipped++;
                        continue;
                    }
                    
                    // Create new prediction
                    Prediction::create([
                        'match' => $item['match'],
                        'country' => $item['country'] ?? null,
                        'league' => $item['league'] ?? null,
                        'date' => $item['date'],
                        'tips' => $item['tips'] ?? [],
                    ]);
                    
                    $imported++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to import prediction: ' . $e->getMessage());
                }
            }
            
            $this->info('Import completed successfully');
            $this->info('Imported: ' . $imported);
            $this->info('Skipped: ' . $skipped);
            $this->info('Errors: ' . $errors);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            Log::error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 