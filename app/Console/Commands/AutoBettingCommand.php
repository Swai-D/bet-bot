<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BetPawaService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class AutoBettingCommand extends Command
{
    protected $signature = 'betting:auto';
    protected $description = 'Run automatic betting based on selected matches';

    private $betPawaService;

    public function __construct(BetPawaService $betPawaService)
    {
        parent::__construct();
        $this->betPawaService = $betPawaService;
    }

    public function handle()
    {
        try {
            $this->info('Starting auto betting...');

            // Check if auto betting is enabled
            if (!Cache::get('auto_betting_enabled', false)) {
                $this->info('Auto betting is disabled');
                return;
            }

            // Check if bot is running
            if (!Cache::get('bot_status', false)) {
                $this->info('Bot is not running');
                return;
            }

            // Get selected matches
            $selectedMatches = Cache::get('selected_matches', []);
            
            if (empty($selectedMatches)) {
                $this->info('No matches selected for betting');
                return;
            }

            // Process each selected match
            foreach ($selectedMatches as $matchId) {
                $matchData = Cache::get('match_' . $matchId);
                
                if (!$matchData) {
                    $this->warn("Match data not found for ID: {$matchId}");
                    continue;
                }

                // Check if odds are favorable
                if (!$this->betPawaService->areOddsFavorable($matchData['odds'])) {
                    $this->info("Odds not favorable for match: {$matchData['match']['home_team']} vs {$matchData['match']['away_team']}");
                    continue;
                }

                // Place bet
                $betPlaced = $this->betPawaService->placeBet(
                    $matchId,
                    $matchData['match']['tips'][0], // Use first tip
                    Cache::get('bet_amount', 1000) // Default bet amount
                );

                if ($betPlaced) {
                    $this->info("Bet placed successfully for match: {$matchData['match']['home_team']} vs {$matchData['match']['away_team']}");
                    
                    // Store bet history
                    $betHistory = Cache::get('bet_history', []);
                    $betHistory[] = [
                        'match_id' => $matchId,
                        'match' => $matchData['match']['home_team'] . ' vs ' . $matchData['match']['away_team'],
                        'tip' => $matchData['match']['tips'][0],
                        'odds' => $matchData['odds'],
                        'stake' => Cache::get('bet_amount', 1000),
                        'placed_at' => now()
                    ];
                    Cache::put('bet_history', $betHistory, now()->addDays(7));
                } else {
                    $this->error("Failed to place bet for match: {$matchData['match']['home_team']} vs {$matchData['match']['away_team']}");
                }
            }

            $this->info('Auto betting completed');

        } catch (\Exception $e) {
            Log::error('Auto betting failed: ' . $e->getMessage());
            $this->error('Auto betting failed: ' . $e->getMessage());
        }
    }
} 