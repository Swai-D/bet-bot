<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupPredictionsCommand extends Command
{
    protected $signature = 'predictions:backup {--date= : Specific date to backup} {--path= : Custom backup path}';
    protected $description = 'Backup predictions to a JSON file';

    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        $path = $this->option('path') ?? storage_path('backups/predictions_' . $date->format('Y-m-d_His') . '.json');

        // Ensure backup directory exists
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Get predictions for the date
        $predictions = \App\Models\Prediction::whereDate('match_date', $date)->get();

        if ($predictions->isEmpty()) {
            $this->error("No predictions found for date: " . $date->format('Y-m-d'));
            return 1;
        }

        // Save to JSON file
        file_put_contents($path, json_encode($predictions, JSON_PRETTY_PRINT));

        $this->info("Successfully backed up " . $predictions->count() . " predictions to: " . $path);
        return 0;
    }
} 