<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:backup {--date= : The date to backup predictions for} {--country= : Filter by country} {--league= : Filter by league} {--path= : The path to save the backup file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup predictions to a JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');
        $country = $this->option('country');
        $league = $this->option('league');
        $path = $this->option('path') ?? storage_path('backups/predictions_' . now()->format('Y-m-d_His') . '.json');
        
        $this->info('Backing up predictions...');
        
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
            
            $data = $predictions->map(function ($prediction) {
                return [
                    'match' => $prediction->match,
                    'country' => $prediction->country,
                    'league' => $prediction->league,
                    'date' => $prediction->date,
                    'tips' => $prediction->tips,
                ];
            })->toArray();
            
            // Create directory if it doesn't exist
            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0755, true);
            }
            
            // Save backup file
            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
            
            $this->info('Backup completed successfully');
            $this->info('Backed up: ' . count($data) . ' predictions');
            $this->info('File saved to: ' . $path);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('Backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 