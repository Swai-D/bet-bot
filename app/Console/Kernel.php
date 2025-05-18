<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\BackupPredictionsCommand::class,
        Commands\CleanupPredictionsCommand::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        // Run betting bot every 5 minutes
        $schedule->command('betting:run')->everyFiveMinutes();

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

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 