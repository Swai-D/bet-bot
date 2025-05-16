<?php

namespace App\Console\Commands;

use App\Services\BetService;
use Illuminate\Console\Command;

class TestPlaceBet extends Command
{
    protected $signature = 'betpawa:test-bet';
    protected $description = 'Test placing a bet on Betpawa';

    public function handle()
    {
        $this->info('Testing bet placement...');

        $betService = app(BetService::class);
        
        // Replace with actual match details
        $matchName = 'Manchester United vs Liverpool';
        $oddType = '1'; // 1 = Home Win, X = Draw, 2 = Away Win
        $amount = 1000; // Amount in TZS

        $result = $betService->placeBet($matchName, $oddType, $amount);

        if ($result['success']) {
            $this->info('Bet placed successfully!');
            $this->info('Match: ' . $result['bet']->match_name);
            $this->info('Odd Type: ' . $result['bet']->odd_type);
            $this->info('Odds: ' . $result['bet']->odds);
            $this->info('Amount: ' . $result['bet']->amount);
            $this->info('Potential Win: ' . $result['bet']->potential_win);
        } else {
            $this->error('Failed to place bet: ' . $result['message']);
        }
    }
} 