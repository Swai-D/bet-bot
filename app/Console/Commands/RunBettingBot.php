<?php

namespace App\Console\Commands;

use App\Services\BetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunBettingBot extends Command
{
    protected $signature = 'betting:run';
    protected $description = 'Run the betting bot';

    protected $betService;

    public function __construct(BetService $betService)
    {
        parent::__construct();
        $this->betService = $betService;
    }

    public function handle()
    {
        $this->info('Starting betting bot...');

        try {
            // Get pending bets
            $pendingBets = $this->betService->getPendingBets();

            foreach ($pendingBets as $bet) {
                $this->info("Checking status for bet: {$bet->match_name}");
                
                $result = $this->betService->checkBetStatus($bet);
                
                if ($result['success']) {
                    if ($bet->isWon()) {
                        $this->info("Bet won! Amount: {$bet->actual_win}");
                    } else {
                        $this->info("Bet lost!");
                    }
                } else {
                    $this->error("Error checking bet status: {$result['message']}");
                }
            }

            // Show statistics
            $this->showStatistics();

        } catch (\Exception $e) {
            Log::error('Error running betting bot: ' . $e->getMessage());
            $this->error('Error running betting bot: ' . $e->getMessage());
        }
    }

    protected function showStatistics()
    {
        $this->info("\nBetting Statistics:");
        $this->info("Total Winnings: " . $this->betService->getTotalWinnings());
        $this->info("Total Losses: " . $this->betService->getTotalLosses());
        $this->info("Profit: " . $this->betService->getProfit());
    }
} 