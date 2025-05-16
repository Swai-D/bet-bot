<?php

namespace App\Console\Commands;

use App\Services\OddsApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestOddsIntegration extends Command
{
    protected $signature = 'odds:test {--limit=5}';
    protected $description = 'Test odds integration with a limited number of matches';

    protected $oddsService;

    public function __construct(OddsApiService $oddsService)
    {
        parent::__construct();
        $this->oddsService = $oddsService;
    }

    public function handle()
    {
        $limit = $this->option('limit');
        $this->info("Testing odds integration with {$limit} matches...");

        // Get some test matches from your database
        $matches = \App\Models\Prediction::take($limit)->get();

        $bar = $this->output->createProgressBar(count($matches));
        $bar->start();

        $results = [];
        foreach ($matches as $match) {
            // Map the league based on the teams
            $league = $this->determineLeague($match->match);
            
            // Use current date for testing since future dates won't have odds
            $testDate = now()->format('Y-m-d H:i:s');

            $odds = $this->oddsService->getMatchOdds(
                $match->match,
                $testDate,
                $league
            );

            // Handle different tip formats
            $tips = $match->tips;
            $formattedTips = 'N/A';
            
            if (is_string($tips)) {
                $formattedTips = $tips;
            } elseif (is_array($tips)) {
                $formattedTips = implode(', ', array_map(function($tip) {
                    if (is_object($tip)) {
                        return $tip->option ?? 'N/A';
                    } elseif (is_array($tip)) {
                        return implode(' ', array_filter($tip, 'is_string'));
                    }
                    return (string) $tip;
                }, $tips));
            } elseif (is_object($tips)) {
                $formattedTips = $tips->option ?? 'N/A';
            }

            $results[] = [
                'match' => $match->match,
                'date' => $match->date,
                'league' => $league,
                'prediction' => $formattedTips,
                'odds' => $odds
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Display results
        $tableData = collect($results)->map(function ($result) {
            return [
                'match' => $result['match'],
                'date' => $result['date'],
                'league' => $result['league'],
                'prediction' => $result['prediction'],
                'home_odds' => $result['odds'] ? ($result['odds']['1'] ?? 'N/A') : 'N/A',
                'draw_odds' => $result['odds'] ? ($result['odds']['X'] ?? 'N/A') : 'N/A',
                'away_odds' => $result['odds'] ? ($result['odds']['2'] ?? 'N/A') : 'N/A',
            ];
        })->toArray();

        $this->table(
            ['Match', 'Date', 'League', 'Prediction', 'Home Odds', 'Draw Odds', 'Away Odds'],
            $tableData
        );

        // Log summary
        $successCount = count(array_filter($results, fn($r) => $r['odds'] !== null));
        $this->info("Successfully found odds for {$successCount} out of {$limit} matches");

        return Command::SUCCESS;
    }

    /**
     * Determine the league based on the teams
     */
    protected function determineLeague(string $match): string
    {
        $match = strtolower($match);
        
        // Map teams to leagues
        $leagueMap = [
            'austria_2' => ['st. polten', 'sk rapid', 'ried', 'bregenz'],
            'belgium' => ['leuven', 'westerlo'],
            'china' => ['yunnan', 'meizhou'],
            'denmark' => ['norsjaelland', 'aarhus'],
            'england' => ['arsenal', 'chelsea', 'manchester', 'liverpool'],
            'germany' => ['bayern', 'dortmund', 'leipzig'],
            'spain' => ['barcelona', 'real madrid', 'atletico'],
            'italy' => ['juventus', 'milan', 'inter'],
            'france' => ['psg', 'lyon', 'marseille'],
            'netherlands' => ['ajax', 'psv', 'feyenoord'],
            'portugal' => ['benfica', 'porto', 'sporting'],
            'turkey' => ['galatasaray', 'fenerbahce', 'besiktas'],
        ];

        foreach ($leagueMap as $league => $teams) {
            foreach ($teams as $team) {
                if (str_contains($match, $team)) {
                    return $league;
                }
            }
        }

        return 'england'; // Default to EPL if no match found
    }
} 