<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\BackupPredictionsCommand::class,
        Commands\CleanupPredictionsCommand::class,
        \App\Console\Commands\InsertBetisValenciaPredictionCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Run scraper every minute to check scheduled time
        $schedule->command('scraper:auto')->everyMinute();

        // Run betting every 5 minutes
        $schedule->command('betting:auto')->everyFiveMinutes();

        // Save predictions every 30 minutes
        $schedule->command('predictions:save')
            ->everyThirtyMinutes()
            ->appendOutputTo(storage_path('logs/predictions.log'));

        // Run predictions cleanup daily at midnight
        $schedule->command('predictions:cleanup')
            ->daily()
            ->at('00:00')
            ->appendOutputTo(storage_path('logs/cleanup.log'));

        // Schedule backup command to run daily at midnight
        $schedule->command('predictions:backup')->daily();
        
        // Schedule cleanup command to run weekly
        $schedule->command('predictions:cleanup')->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 