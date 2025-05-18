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
                            {--league= : Filter by league name}
                            {--country= : Filter by country}
                            {--from= : Show predictions from date}
                            {--to= : Show predictions to date}
                            {--min-odds= : Minimum odds value}
                            {--max-odds= : Maximum odds value}
                            {--detailed : Show detailed view with more information}
                            {--stats : Show prediction statistics}';

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

        if ($from = $this->option('from')) {
            $query->whereDate('date', '>=', Carbon::parse($from));
            $this->info("📅 Showing predictions from: {$from}");
        }

        if ($to = $this->option('to')) {
            $query->whereDate('date', '<=', Carbon::parse($to));
            $this->info("📅 Showing predictions to: {$to}");
        }

        if ($team = $this->option('team')) {
            $query->where('match', 'like', "%{$team}%");
            $this->info("🏟️ Filtering by team: {$team}");
        }

        if ($league = $this->option('league')) {
            $query->where('league', 'like', "%{$league}%");
            $this->info("🏆 Filtering by league: {$league}");
        }

        if ($country = $this->option('country')) {
            $query->where('country', 'like', "%{$country}%");
            $this->info("🌍 Filtering by country: {$country}");
        }

        if ($tip = $this->option('tip')) {
            $query->whereHas('tips', function($q) use ($tip) {
                $q->where('option', $tip);
            });
            $this->info("🎯 Filtering by tip: {$tip}");
        }

        if ($minOdds = $this->option('min-odds')) {
            $query->whereHas('tips', function($q) use ($minOdds) {
                $q->where('odd', '>=', $minOdds);
            });
            $this->info("📈 Minimum odds: {$minOdds}");
        }

        if ($maxOdds = $this->option('max-odds')) {
            $query->whereHas('tips', function($q) use ($maxOdds) {
                $q->where('odd', '<=', $maxOdds);
            });
            $this->info("📉 Maximum odds: {$maxOdds}");
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

        if ($this->option('stats')) {
            $this->showStatistics($predictions);
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

        $headers = ['Match', 'Date', 'League', 'Country', 'Tips'];
        $rows = $predictions->map(function ($prediction) {
            return [
                'match' => $this->formatTeams($prediction->match),
                'date' => $this->formatDate($prediction->date),
                'league' => $prediction->league,
                'country' => $prediction->country,
                'tips' => $this->formatTips($prediction->tips)
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
        $this->line('║ League: ' . str_pad($prediction->league, 50) . ' ║');
        $this->line('║ Country: ' . str_pad($prediction->country, 50) . ' ║');
        $this->line('║ Tips: ' . $this->formatTips($prediction->tips, 52) . ' ║');
        $this->line('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showStatistics($predictions)
    {
        $this->newLine();
        $this->info('📈 PREDICTION STATISTICS');
        $this->newLine();

        // League statistics
        $leagueStats = $predictions->groupBy('league')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg_odds' => $group->flatMap->tips->avg('odd')
                ];
            })
            ->sortByDesc('count');

        $this->info('🏆 League Statistics:');
        $this->table(
            ['League', 'Predictions', 'Avg Odds'],
            $leagueStats->map(function ($stats, $league) {
                return [
                    $league,
                    $stats['count'],
                    number_format($stats['avg_odds'], 2)
                ];
            })
        );

        // Country statistics
        $countryStats = $predictions->groupBy('country')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'leagues' => $group->pluck('league')->unique()->count()
                ];
            })
            ->sortByDesc('count');

        $this->info('🌍 Country Statistics:');
        $this->table(
            ['Country', 'Predictions', 'Leagues'],
            $countryStats->map(function ($stats, $country) {
                return [
                    $country,
                    $stats['count'],
                    $stats['leagues']
                ];
            })
        );

        // Tips statistics
        $tipsStats = $predictions->flatMap->tips
            ->groupBy('option')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'avg_odds' => $group->avg('odd')
                ];
            })
            ->sortByDesc('count');

        $this->info('🎯 Tips Statistics:');
        $this->table(
            ['Tip', 'Count', 'Avg Odds'],
            $tipsStats->map(function ($stats, $tip) {
                return [
                    $tip,
                    $stats['count'],
                    number_format($stats['avg_odds'], 2)
                ];
            })
        );
    }

    protected function showSummary($predictions)
    {
        $this->newLine();
        $this->info('📊 SUMMARY');
        $this->newLine();

        $totalPredictions = $predictions->count();
        $totalTips = $predictions->sum(function($p) {
            return count($p->tips);
        });
        $avgOdds = $predictions->flatMap->tips->avg('odd');
        $uniqueLeagues = $predictions->pluck('league')->unique()->count();
        $uniqueCountries = $predictions->pluck('country')->unique()->count();

        $this->line('╔════════════════════════════════════════════════════════════╗');
        $this->line('║ Total Predictions: ' . str_pad($totalPredictions, 39) . ' ║');
        $this->line('║ Total Tips: ' . str_pad($totalTips, 45) . ' ║');
        $this->line('║ Average Odds: ' . str_pad(number_format($avgOdds, 2), 43) . ' ║');
        $this->line('║ Unique Leagues: ' . str_pad($uniqueLeagues, 41) . ' ║');
        $this->line('║ Unique Countries: ' . str_pad($uniqueCountries, 39) . ' ║');
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