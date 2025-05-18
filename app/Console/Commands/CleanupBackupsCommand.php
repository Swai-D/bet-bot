<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class CleanupBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:backup-cleanup {--path= : The path to the backups directory} {--days=30 : Number of days to keep backups} {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old prediction backup files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->option('path') ?? storage_path('backups');
        $days = (int) $this->option('days');
        $force = $this->option('force');
        
        if (!is_dir($path)) {
            $this->error('Backups directory not found: ' . $path);
            return Command::FAILURE;
        }
        
        $this->info('Cleaning up backup files older than ' . $days . ' days...');
        
        try {
            $files = File::files($path);
            
            if (empty($files)) {
                $this->info('No backup files found');
                return Command::SUCCESS;
            }
            
            $cutoffDate = now()->subDays($days)->timestamp;
            
            $filesToDelete = collect($files)
                ->filter(function ($file) use ($cutoffDate) {
                    return $file->getExtension() === 'json' && $file->getMTime() < $cutoffDate;
                })
                ->values();
            
            if ($filesToDelete->isEmpty()) {
                $this->info('No old backup files found');
                return Command::SUCCESS;
            }
            
            if (!$force && !$this->confirm("Are you sure you want to delete " . $filesToDelete->count() . " old backup files?")) {
                $this->info('Cleanup cancelled');
                return Command::SUCCESS;
            }
            
            $deleted = 0;
            $errors = 0;
            
            foreach ($filesToDelete as $file) {
                try {
                    if (File::delete($file->getPathname())) {
                        $deleted++;
                    } else {
                        $errors++;
                        Log::error('Failed to delete backup file: ' . $file->getFilename());
                    }
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Failed to delete backup file: ' . $e->getMessage());
                }
            }
            
            $this->info('Cleanup completed successfully');
            $this->info('Deleted: ' . $deleted . ' files');
            $this->info('Errors: ' . $errors);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Cleanup failed: ' . $e->getMessage());
            Log::error('Cleanup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 