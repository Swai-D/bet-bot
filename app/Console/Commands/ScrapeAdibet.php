<?php

namespace App\Console\Commands;

use App\Models\Prediction;
use App\Models\Tip;
use App\Services\AdibetScraper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ShowPredictions extends Command
{
    protected $signature = 'predictions:show {--best : Show only top tier matches} {--moderate : Show only moderate tier matches} {--date= : Show matches for specific date}';
    protected $description = 'Show predictions from Adibet';

    protected $scraper;

    public function __construct(AdibetScraper $scraper)
    {
        parent::__construct();
        $this->scraper = $scraper;
    }

    public function handle()
    {
        $this->info('Fetching predictions from Adibet...');

        try {
            // Get predictions from Adibet
            $predictions = $this->scraper->fetchPredictions();
            
            if (empty($predictions)) {
                $this->error('No predictions found from Adibet');
                return;
            }

            // Filter by date if specified
            if ($date = $this->option('date')) {
                $predictions = collect($predictions)->filter(function ($prediction) use ($date) {
                    return $prediction['date'] === $date;
                })->values()->all();
            } else {
                // Default to today's date
                $today = Carbon::now()->format('Y-m-d');
                $predictions = collect($predictions)->filter(function ($prediction) use ($today) {
                    return $prediction['date'] === $today;
                })->values()->all();
            }

            // Filter by tier if specified
            if ($this->option('best')) {
                $predictions = collect($predictions)->filter(function ($prediction) {
                    return $prediction['score'] >= 4; // Top tier matches
                })->values()->all();
            } elseif ($this->option('moderate')) {
                $predictions = collect($predictions)->filter(function ($prediction) {
                    return $prediction['score'] >= 2 && $prediction['score'] < 4; // Moderate tier matches
                })->values()->all();
            }

            // Display predictions
            foreach ($predictions as $prediction) {
                // Get only highlighted tips
                $highlightedTips = collect($prediction['tips'])->filter(function ($tip) {
                    return $tip['selected'];
                })->values()->all();

                // Skip if no highlighted tips
                if (empty($highlightedTips)) {
                    continue;
                }

                $this->line('');
                $this->line("Match: {$prediction['match']}");
                $this->line("Country: {$prediction['country']}");
                $this->line("League: {$prediction['league']}");
                $this->line("Date: {$prediction['date']}");
                $this->line("Tips:");
                foreach ($highlightedTips as $tip) {
                    $this->line("  - {$tip['option']} ({$tip['name']})");
                }
                $this->line(str_repeat('-', 50));
            }

            $this->info('Successfully displayed ' . count($predictions) . ' predictions');
        } catch (\Exception $e) {
            $this->error('Error fetching predictions: ' . $e->getMessage());
            Log::error('Error fetching predictions: ' . $e->getMessage());
        }
    }
} 