<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RestoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:restore {file : The path to the backup file} {--force : Force restore without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore predictions from a backup file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $force = $this->option('force');
        
        if (!file_exists($file)) {
            $this->error('Backup file not found: ' . $file);
            return Command::FAILURE;
        }
        
        $this->info('Restoring predictions from: ' . $file);
        
        try {
            $data = json_decode(file_get_contents($file), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid backup file');
                return Command::FAILURE;
            }
            
            if (empty($data)) {
                $this->info('No predictions found in backup file');
                return Command::SUCCESS;
            }
            
            if (!$force && !$this->confirm("Are you sure you want to restore " . count($data) . " predictions?")) {
                $this->info('Restore cancelled');
                return Command::SUCCESS;
            }
            
            $restored = 0;
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
                    
                    $restored++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to restore prediction: ' . $e->getMessage());
                }
            }
            
            $this->info('Restore completed successfully');
            $this->info('Restored: ' . $restored);
            $this->info('Skipped: ' . $skipped);
            $this->info('Errors: ' . $errors);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Restore failed: ' . $e->getMessage());
            Log::error('Restore failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 