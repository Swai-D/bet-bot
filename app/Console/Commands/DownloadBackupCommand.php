<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DownloadBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:backup-download {file : The backup file to download} {--path= : The path to the backups directory} {--output= : The path to save the downloaded file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download a prediction backup file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $path = $this->option('path') ?? storage_path('backups');
        $output = $this->option('output') ?? getcwd() . '/' . $file;
        
        $filePath = $path . '/' . $file;
        
        if (!file_exists($filePath)) {
            $this->error('Backup file not found: ' . $filePath);
            return Command::FAILURE;
        }
        
        $this->info('Downloading backup file: ' . $file);
        
        try {
            // Create output directory if it doesn't exist
            $outputDir = dirname($output);
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }
            
            // Copy file to output location
            if (File::copy($filePath, $output)) {
                $this->info('Backup file downloaded successfully');
                $this->info('Saved to: ' . $output);
                return Command::SUCCESS;
            } else {
                $this->error('Failed to download backup file');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Failed to download backup file: ' . $e->getMessage());
            Log::error('Failed to download backup file: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 