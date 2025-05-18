<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DeletePredictionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:delete {--date= : The date to delete predictions for} {--country= : Filter by country} {--league= : Filter by league} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete predictions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date');
        $country = $this->option('country');
        $league = $this->option('league');
        $force = $this->option('force');
        
        $this->info('Deleting predictions...');
        
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
            
            $count = $query->count();
            
            if ($count === 0) {
                $this->info('No predictions found');
                return Command::SUCCESS;
            }
            
            if (!$force && !$this->confirm("Are you sure you want to delete {$count} predictions?")) {
                $this->info('Deletion cancelled');
                return Command::SUCCESS;
            }
            
            $deleted = $query->delete();
            
            $this->info('Deletion completed successfully');
            $this->info('Deleted: ' . $deleted . ' predictions');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Deletion failed: ' . $e->getMessage());
            Log::error('Deletion failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 