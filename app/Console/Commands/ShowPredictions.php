<?php

namespace App\Console\Commands;

use App\Services\AdibetScraper;
use Illuminate\Console\Command;

class ShowPredictions extends Command
{
    protected $signature = 'predictions:show 
        {--date= : The date to show predictions for (format: Y-m-d)} 
        {--best : Show only top tier league matches} 
        {--moderate : Show only moderate tier league matches}';

    protected $description = 'Show predictions from Adibet';

    public function handle()
    {
        $date = $this->option('date');
        $showBest = $this->option('best');
        $showModerate = $this->option('moderate');
        $scraper = new AdibetScraper();
        
        $this->info('Fetching predictions from Adibet...');
        
        if ($showBest) {
            $predictions = $scraper->getBestMatches();
            $this->info("\nFound " . count($predictions) . " top tier matches:\n");
        } elseif ($showModerate) {
            $predictions = $scraper->getModerateMatches();
            $this->info("\nFound " . count($predictions) . " moderate tier matches:\n");
        } else {
            $predictions = $scraper->fetchPredictions();
            $this->info("\nFound " . count($predictions) . " all matches:\n");
        }

        if (empty($predictions)) {
            $this->error('No predictions found!');
            return Command::FAILURE;
        }

        foreach ($predictions as $prediction) {
            $this->line("\nMatch: {$prediction['match']}");
            $this->line("Country: {$prediction['country']}");
            $this->line("Date: {$prediction['date']}");
            $this->line("Tips:");
            
            foreach ($prediction['tips'] as $tip) {
                $this->line("  - {$tip['option']}");
            }
            
            $this->line(str_repeat('-', 50));
        }

        return Command::SUCCESS;
    }
} 