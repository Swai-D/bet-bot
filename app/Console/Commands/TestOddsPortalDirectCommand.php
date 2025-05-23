<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OddsPortalScraper;
use Carbon\Carbon;

class TestOddsPortalDirectCommand extends Command
{
    protected $signature = 'test:oddsportal-direct';
    protected $description = 'Test OddsPortal scraper directly with specific matches';

    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                ODDS PORTAL DIRECT TEST                      ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $scraper = new OddsPortalScraper();
        
        // Test matches for today and tomorrow
        $testMatches = [
            [
                'match' => 'Manchester City vs Arsenal',
                'tip' => '1'
            ],
            [
                'match' => 'Liverpool vs Brighton',
                'tip' => '1'
            ],
            [
                'match' => 'Barcelona vs Real Madrid',
                'tip' => '1'
            ]
        ];

        $this->info('Testing matches:');
        $this->newLine();

        foreach ($testMatches as $testMatch) {
            $this->line("Processing: {$testMatch['match']} (Tip: {$testMatch['tip']})");
            
            $odds = $scraper->getOdds($testMatch['match'], $testMatch['tip']);
            
            if ($odds) {
                $this->info("✅ Success - Odd: {$odds['value']} (Bookmaker: {$odds['bookmaker']})");
            } else {
                $this->error("❌ Failed to get odds");
            }
            
            $this->newLine();
        }

        $this->info('Test completed!');
    }
} 
 