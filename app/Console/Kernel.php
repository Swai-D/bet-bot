<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
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
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
} 