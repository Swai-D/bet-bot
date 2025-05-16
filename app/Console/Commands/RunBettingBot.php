<?php

namespace App\Console\Commands;

use App\Services\BettingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunBettingBot extends Command
{
    protected $signature = 'betting:run {--stake=1000 : The stake amount to bet}';
    protected $description = 'Run the betting bot to scrape matches and place bets';

    protected $bettingService;

    public function __construct(BettingService $bettingService)
    {
        parent::__construct();
        $this->bettingService = $bettingService;
    }

    public function handle()
    {
        try {
            $this->info('Starting betting process...');

            // Step 1: Scrape matches
            $this->info('Scraping matches from Adibet...');
            $matches = $this->bettingService->scrapeMatches();
            $this->info('Successfully scraped matches');

            // Step 2: Select matches
            $this->info('Selecting matches with odds ≥ 3.00...');
            $betData = $this->bettingService->selectMatches($matches);
            $this->info('Selected ' . count($betData['tips']) . ' matches');

            // Step 3: Place bet
            $this->info('Placing bet on Betpawa...');
            $result = $this->bettingService->placeBet($betData);
            
            $this->info('Bet placed successfully!');
            $this->table(
                ['Match', 'Option', 'Odd'],
                collect($betData['tips'])->map(function ($tip) {
                    return [
                        'match' => $tip['match'],
                        'option' => $tip['option'],
                        'odd' => $tip['odd']
                    ];
                })
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            Log::error('Betting bot failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 