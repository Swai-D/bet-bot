<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class DeleteBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:backup-delete {file : The backup file to delete} {--path= : The path to the backups directory} {--force : Force deletion without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete a prediction backup file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $path = $this->option('path') ?? storage_path('backups');
        $force = $this->option('force');
        
        $filePath = $path . '/' . $file;
        
        if (!file_exists($filePath)) {
            $this->error('Backup file not found: ' . $filePath);
            return Command::FAILURE;
        }
        
        $this->info('Deleting backup file: ' . $file);
        
        try {
            if (!$force && !$this->confirm("Are you sure you want to delete this backup file?")) {
                $this->info('Deletion cancelled');
                return Command::SUCCESS;
            }
            
            if (File::delete($filePath)) {
                $this->info('Backup file deleted successfully');
                return Command::SUCCESS;
            } else {
                $this->error('Failed to delete backup file');
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('Failed to delete backup file: ' . $e->getMessage());
            Log::error('Failed to delete backup file: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 