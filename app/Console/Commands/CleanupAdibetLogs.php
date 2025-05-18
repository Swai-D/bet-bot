<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CleanupAdibetLogs extends Command
{
    protected $signature = 'cleanup:adibet-logs';
    protected $description = 'Clean up old Adibet logs';

    public function handle()
    {
        try {
            $oldGames = Game::where('created_at', '<', now()->subDays(7))->get();
            $count = $oldGames->count();

            $oldGames->each->delete();

            $this->info("Successfully cleaned up {$count} old games");
            Log::info('Successfully cleaned up old games', ['count' => $count]);

        } catch (\Exception $e) {
            $this->error('Failed to clean up old games: ' . $e->getMessage());
            Log::error('Failed to clean up old games', ['error' => $e->getMessage()]);
        }
    }
} 