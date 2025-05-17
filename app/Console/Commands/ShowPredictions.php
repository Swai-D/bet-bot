<?php

namespace App\Console\Commands;

use App\Models\Prediction;
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

        $query = Prediction::query();

        // Apply filters
        if ($this->option('best')) {
            $query->whereHas('tips', function($q) {
                $q->where('odd', '>=', 2.5);
            });
            $this->info('🔝 Showing best predictions (odds >= 2.5)');
        }

        if ($this->option('moderate')) {
            $query->whereHas('tips', function($q) {
                $q->whereBetween('odd', [1.5, 2.5]);
            });
            $this->info('⚖️ Showing moderate predictions (odds between 1.5 and 2.5)');
        }

        if ($date = $this->option('date')) {
            $query->whereDate('date', Carbon::parse($date));
            $this->info("📅 Showing predictions for date: {$date}");
        }

        if ($team = $this->option('team')) {
            $query->where('match', 'like', "%{$team}%");
            $this->info("🏟️ Filtering by team: {$team}");
        }

        if ($tip = $this->option('tip')) {
            $query->whereHas('tips', function($q) use ($tip) {
                $q->where('option', $tip);
            });
            $this->info("🎯 Filtering by tip: {$tip}");
        }

        // Get predictions
        $predictions = $query->with('tips')->latest()->get();

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

        $headers = ['Match', 'Date', 'Tips', 'Country'];
        $rows = $predictions->map(function ($prediction) {
            return [
                'match' => $this->formatTeams($prediction->match),
                'date' => $this->formatDate($prediction->date),
                'tips' => $this->formatTips($prediction->tips),
                'country' => $prediction->country
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
        $this->line('║ ' . $this->formatTeams($prediction->match, 58) . ' ║');
        $this->line('╠════════════════════════════════════════════════════════════╣');
        $this->line('║ Date: ' . $this->formatDate($prediction->date, 52) . ' ║');
        $this->line('║ Country: ' . str_pad($prediction->country, 50) . ' ║');
        $this->line('║ Tips: ' . $this->formatTips($prediction->tips, 52) . ' ║');
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showSummary($predictions)
    {
        $this->newLine();
        $this->info('📈 SUMMARY');
        $this->newLine();

        $totalPredictions = $predictions->count();
        $totalTips = $predictions->sum(function($p) {
            return count($p->tips);
        });

        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║ Total Predictions: ' . str_pad($totalPredictions, 39) . ' ║');
        $this->line('║ Total Tips: ' . str_pad($totalTips, 45) . ' ║');
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
        $formatted = Carbon::parse($date)->format('Y-m-d H:i');
        return $length ? str_pad($formatted, $length) : $formatted;
    }

    protected function formatTips($tips, $length = null)
    {
        $formatted = collect($tips)->map(function($tip) {
            return $tip['option'] . ($tip['odd'] ? ' (' . number_format($tip['odd'], 2) . ')' : '');
        })->join(', ');
        
        return $length ? str_pad($formatted, $length) : $formatted;
    }
} 