<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prediction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StatsCommand extends Command
{
    protected $signature = 'predictions:stats 
                            {--date= : Show stats for specific date}
                            {--days= : Show stats for last X days}
                            {--country= : Filter by country}
                            {--league= : Filter by league}';
    protected $description = 'Show prediction statistics';

    public function handle()
    {
        $date = $this->option('date');
        $days = $this->option('days');
        $country = $this->option('country');
        $league = $this->option('league');

        $query = Prediction::query();

        if ($date) {
            $query->whereDate('match_date', Carbon::parse($date));
        } elseif ($days) {
            $query->where('match_date', '>=', Carbon::now()->subDays($days));
        }

        if ($country) {
            $query->where('country', 'like', "%{$country}%");
        }

        if ($league) {
            $query->where('league', 'like', "%{$league}%");
        }

        $predictions = $query->get();

        if ($predictions->isEmpty()) {
            $this->error("No predictions found for the specified criteria");
            return 1;
        }

        $this->showGeneralStats($predictions);
        $this->showCountryStats($predictions);
        $this->showLeagueStats($predictions);
        $this->showTipsStats($predictions);

        return 0;
    }

    protected function showGeneralStats($predictions)
    {
        $this->info("\n📊 General Statistics");
        $this->line("----------------------------------------");
        $this->line("Total Predictions: " . $predictions->count());
        $this->line("Date Range: " . $predictions->min('match_date')->format('Y-m-d') . " to " . $predictions->max('match_date')->format('Y-m-d'));
        $this->line("Unique Countries: " . $predictions->pluck('country')->unique()->count());
        $this->line("Unique Leagues: " . $predictions->pluck('league')->unique()->count());
    }

    protected function showCountryStats($predictions)
    {
        $this->info("\n🌍 Country Statistics");
        $this->line("----------------------------------------");
        
        $countryStats = $predictions->groupBy('country')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'leagues' => $group->pluck('league')->unique()->count()
                ];
            })
            ->sortByDesc('count');

        $headers = ['Country', 'Predictions', 'Leagues'];
        $rows = $countryStats->map(function ($stats, $country) {
            return [$country, $stats['count'], $stats['leagues']];
        });

        $this->table($headers, $rows);
    }

    protected function showLeagueStats($predictions)
    {
        $this->info("\n🏆 League Statistics");
        $this->line("----------------------------------------");
        
        $leagueStats = $predictions->groupBy('league')
            ->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'country' => $group->first()->country
                ];
            })
            ->sortByDesc('count');

        $headers = ['League', 'Country', 'Predictions'];
        $rows = $leagueStats->map(function ($stats, $league) {
            return [$league, $stats['country'], $stats['count']];
        });

        $this->table($headers, $rows);
    }

    protected function showTipsStats($predictions)
    {
        $this->info("\n🎯 Tips Statistics");
        $this->line("----------------------------------------");
        
        $tipsCount = [];
        foreach ($predictions as $prediction) {
            foreach ($prediction->tips as $tip) {
                $tipsCount[$tip] = ($tipsCount[$tip] ?? 0) + 1;
            }
        }

        arsort($tipsCount);

        $headers = ['Tip', 'Count', 'Percentage'];
        $rows = [];
        $total = array_sum($tipsCount);

        foreach ($tipsCount as $tip => $count) {
            $percentage = round(($count / $total) * 100, 2);
            $rows[] = [$tip, $count, $percentage . '%'];
        }

        $this->table($headers, $rows);
    }
} 