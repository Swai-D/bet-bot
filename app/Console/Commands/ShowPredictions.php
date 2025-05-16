<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShowPredictions extends Command
{
    protected $signature = 'predictions:show 
                            {--best : Show only best predictions (odds >= 2.5)}
                            {--moderate : Show moderate predictions (odds between 1.5 and 2.5)}
                            {--date= : Show predictions for specific date}
                            {--team= : Filter by team name}
                            {--tip= : Filter by tip type (1, X, 2)}
                            {--detailed : Show detailed view with more information}';

    protected $description = 'Show betting predictions with various filters';

    public function handle()
    {
        $this->showHeader();

        $query = Game::query();

        // Apply filters
        if ($this->option('best')) {
            $query->where('odds', '>=', 2.5);
            $this->info('🔝 Showing best predictions (odds >= 2.5)');
        }

        if ($this->option('moderate')) {
            $query->whereBetween('odds', [1.5, 2.5]);
            $this->info('⚖️ Showing moderate predictions (odds between 1.5 and 2.5)');
        }

        if ($date = $this->option('date')) {
            $query->whereDate('match_date', Carbon::parse($date));
            $this->info("📅 Showing predictions for date: {$date}");
        }

        if ($team = $this->option('team')) {
            $query->where('teams', 'like', "%{$team}%");
            $this->info("🏟️ Filtering by team: {$team}");
        }

        if ($tip = $this->option('tip')) {
            $query->where('tips', $tip);
            $this->info("🎯 Filtering by tip: {$tip}");
        }

        // Get predictions
        $predictions = $query->latest()->get();

        if ($predictions->isEmpty()) {
            $this->showNoPredictions();
            return;
        }

        if ($this->option('detailed')) {
            $this->showDetailedView($predictions);
        } else {
            $this->showCompactView($predictions);
        }

        $this->showSummary($predictions);
    }

    protected function showHeader()
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                    BETTING PREDICTIONS                     ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showNoPredictions()
    {
        $this->newLine();
        $this->error('╔════════════════════════════════════════════════════════════╗');
        $this->error('║                 NO PREDICTIONS FOUND!                      ║');
        $this->error('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showCompactView($predictions)
    {
        $this->newLine();
        $this->info('📊 PREDICTIONS TABLE');
        $this->newLine();

        $headers = ['Match', 'Date', 'Tip', 'Odds', 'Selected'];
        $rows = $predictions->map(function ($prediction) {
            return [
                'teams' => $this->formatTeams($prediction->teams),
                'date' => $this->formatDate($prediction->match_date),
                'tip' => $this->formatTip($prediction->tips),
                'odds' => $this->formatOdds($prediction->odds),
                'selected' => $this->formatSelected($prediction->selected)
            ];
        });

        $this->table($headers, $rows);
    }

    protected function showDetailedView($predictions)
    {
        $this->newLine();
        $this->info('📋 DETAILED PREDICTIONS');
        $this->newLine();

        foreach ($predictions as $prediction) {
            $this->showPredictionDetails($prediction);
        }
    }

    protected function showPredictionDetails($prediction)
    {
        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║ ' . $this->formatTeams($prediction->teams, 58) . ' ║');
        $this->line('╠════════════════════════════════════════════════════════════╣');
        $this->line('║ Date: ' . $this->formatDate($prediction->match_date, 52) . ' ║');
        $this->line('║ Tip:  ' . $this->formatTip($prediction->tips, 52) . ' ║');
        $this->line('║ Odds: ' . $this->formatOdds($prediction->odds, 52) . ' ║');
        $this->line('║ Status: ' . $this->formatSelected($prediction->selected, 50) . ' ║');
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showSummary($predictions)
    {
        $this->newLine();
        $this->info('📈 SUMMARY');
        $this->newLine();

        $totalPredictions = $predictions->count();
        $selectedPredictions = $predictions->where('selected', true)->count();
        $averageOdds = $predictions->whereNotNull('odds')->avg('odds');

        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║ Total Predictions: ' . str_pad($totalPredictions, 39) . ' ║');
        $this->line('║ Selected Predictions: ' . str_pad($selectedPredictions, 36) . ' ║');
        
        if ($averageOdds) {
            $this->line('║ Average Odds: ' . str_pad(number_format($averageOdds, 2), 43) . ' ║');
        }
        
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function formatTeams($teams, $length = null)
    {
        $formatted = Str::of($teams)->title();
        return $length ? str_pad($formatted, $length) : $formatted;
    }

    protected function formatDate($date, $length = null)
    {
        $formatted = $date->format('Y-m-d H:i');
        return $length ? str_pad($formatted, $length) : $formatted;
    }

    protected function formatTip($tip, $length = null)
    {
        $formatted = match($tip) {
            '1' => '🏠 Home Win',
            'X' => '🤝 Draw',
            '2' => '✈️ Away Win',
            default => $tip
        };
        return $length ? str_pad($formatted, $length) : $formatted;
    }

    protected function formatOdds($odds, $length = null)
    {
        if (!$odds) return $length ? str_pad('N/A', $length) : 'N/A';
        
        $formatted = number_format($odds, 2);
        $color = match(true) {
            $odds >= 2.5 => 'green',
            $odds >= 1.5 => 'yellow',
            default => 'red'
        };
        
        $formatted = "<fg={$color}>{$formatted}</>";
        return $length ? str_pad($formatted, $length) : $formatted;
    }

    protected function formatSelected($selected, $length = null)
    {
        $formatted = $selected ? '✅ Selected' : '❌ Not Selected';
        return $length ? str_pad($formatted, $length) : $formatted;
    }
} 