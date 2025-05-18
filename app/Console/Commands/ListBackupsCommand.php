<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListBackupsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'predictions:backups {--path= : The path to the backups directory}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List prediction backup files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $path = $this->option('path') ?? storage_path('backups');
        
        if (!is_dir($path)) {
            $this->error('Backups directory not found: ' . $path);
            return Command::FAILURE;
        }
        
        $this->info('Listing backup files...');
        
        try {
            $files = File::files($path);
            
            if (empty($files)) {
                $this->info('No backup files found');
                return Command::SUCCESS;
            }
            
            $headers = ['Filename', 'Size', 'Last Modified'];
            
            $rows = collect($files)
                ->filter(function ($file) {
                    return $file->getExtension() === 'json';
                })
                ->map(function ($file) {
                    return [
                        $file->getFilename(),
                        $this->formatSize($file->getSize()),
                        $file->getMTime() ? date('Y-m-d H:i:s', $file->getMTime()) : 'N/A',
                    ];
                })
                ->toArray();
            
            if (empty($rows)) {
                $this->info('No JSON backup files found');
                return Command::SUCCESS;
            }
            
            $this->table($headers, $rows);
            
            $this->info('Total: ' . count($rows) . ' backup files');
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to list backup files: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Format file size in bytes to human readable format.
     */
    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 