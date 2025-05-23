<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdibetScraper;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SavePredictions extends Command
{
    protected $signature = 'predictions:save 
                            {--date= : Specific date to fetch predictions for}
                            {--league= : Filter by league name}
                            {--country= : Filter by country}
                            {--force : Force update existing predictions}
                            {--dry-run : Show what would be saved without actually saving}
                            {--detailed : Show detailed output}';

    protected $description = 'Fetch and save predictions from Adibet';

    // Define valid tip options
    protected $validTipOptions = [
        '1', 'X', '2',           // Match result
        '-2.5', '+2.5',          // Handicap
        'GG'                     // Both teams to score
    ];

    public function handle()
    {
        $this->showHeader();

        try {
            $scraper = new AdibetScraper();
            
            // Get options
            $date = $this->option('date');
            $league = $this->option('league');
            $country = $this->option('country');
            $force = $this->option('force');
            $dryRun = $this->option('dry-run');
            $verbose = $this->option('detailed');

            // Show options being used
            if ($verbose) {
                $this->showOptions($date, $league, $country, $force, $dryRun);
            }

            // Fetch predictions
            $this->info('Fetching predictions...');
            $predictions = $scraper->fetchPredictions();
            
            if (empty($predictions)) {
                $this->showNoPredictions();
                return Command::FAILURE;
            }
            
            // Apply filters
            if ($date) {
                $predictions = collect($predictions)->filter(function ($prediction) use ($date) {
                    return Carbon::parse($prediction['date'])->format('Y-m-d') === Carbon::parse($date)->format('Y-m-d');
                })->values()->all();
            }

            if ($league) {
                $predictions = collect($predictions)->filter(function ($prediction) use ($league) {
                    return stripos($prediction['league'] ?? '', $league) !== false;
                })->values()->all();
            }

            if ($country) {
                $predictions = collect($predictions)->filter(function ($prediction) use ($country) {
                    return stripos($prediction['country'] ?? '', $country) !== false;
                })->values()->all();
            }

            if (empty($predictions)) {
                $this->showNoPredictions();
                return Command::FAILURE;
            }

            $this->info('Found ' . count($predictions) . ' predictions');
            
            if ($verbose) {
                $this->showPredictionsSummary($predictions);
            }

            if ($dryRun) {
                $this->info('Dry run mode - no predictions will be saved');
                return Command::SUCCESS;
            }

            // Calculate scores and format predictions
            $formattedPredictions = collect($predictions)->map(function ($prediction) {
                // Calculate score based on league tier and tips
                $score = $this->calculateScore($prediction);
                
                // Format tips to include odds and status
                $tips = collect($prediction['tips'] ?? [])
                    ->filter(function ($tip) {
                        // Only keep tips that are selected/highlighted
                        return is_array($tip) ? ($tip['selected'] ?? false) : false;
                    })
                    ->map(function ($tip) use ($prediction) {
                        // Extract just the option without odds
                        $option = $tip['option'] ?? $tip;
                        
                        // Validate and format the tip option
                        $option = $this->formatTipOption($option);
                        
                        // Get odds from FlashscoreScraper
                        $odds = $this->getOddsForTip($option, $prediction['match'], $prediction['date']);
                        
                        return [
                            'option' => $option,
                            'odd' => $odds,
                            'status' => 'not selected' // Default status
                        ];
                    })
                    ->values()
                    ->toArray();
                
                return [
                    'match_id' => $prediction['match_id'] ?? md5($prediction['match'] . $prediction['date']),
                    'match' => $prediction['match'],
                    'country' => $prediction['country'],
                    'league' => $prediction['league'] ?? 'Unknown League',
                    'date' => $prediction['date'],
                    'score' => $score,
                    'tips' => $tips
                ];
            })->all();

            // Save predictions
            $this->info('Saving predictions to database...');
            $result = $scraper->savePredictions($formattedPredictions);
            
            $this->showResults($result);
            
            Log::info('Predictions saved successfully', $result);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->showError($e);
            return Command::FAILURE;
        }
    }

    protected function formatTipOption($option)
    {
        // Convert to uppercase for consistency
        $option = strtoupper($option);
        
        // Handle common variations
        $option = str_replace(['OVER', 'UNDER'], ['+', '-'], $option);
        $option = str_replace(['BOTH TEAMS TO SCORE', 'BTS'], 'GG', $option);
        $option = str_replace(['NO GOAL', 'NO BTS'], 'NG', $option);
        
        // Handle handicap variations
        if (preg_match('/^([+-]?\d+\.?\d*)$/', $option, $matches)) {
            $number = floatval($matches[1]);
            if ($number > 0) {
                return '+' . $number;
            }
            return $number;
        }
        
        return $option;
    }

    protected function calculateScore($prediction)
    {
        $score = 0;
        
        // League tier score
        $leagueScores = [
            'top' => [
                'England' => 5,
                'Germany' => 4,
                'Spain' => 4,
                'Italy' => 3,
                'France' => 3
            ],
            'moderate' => [
                'Netherlands' => 2,
                'Portugal' => 2,
                'Turkey' => 2,
                'Belgium' => 2,
                'Scotland' => 2
            ]
        ];

        // Check country in top tier
        foreach ($leagueScores['top'] as $country => $points) {
            if (stripos($prediction['country'], $country) !== false) {
                $score += $points;
                break;
            }
        }

        // Check country in moderate tier
        foreach ($leagueScores['moderate'] as $country => $points) {
            if (stripos($prediction['country'], $country) !== false) {
                $score += $points;
                break;
            }
        }

        // Add points for tips (0.5 points per tip)
        $score += (count($prediction['tips'] ?? []) * 0.5);

        // Add points for important matches (2 points)
        $importantMatches = [
            'Manchester' => ['United', 'City'],
            'Barcelona' => ['Real Madrid'],
            'Milan' => ['Inter'],
            'Liverpool' => ['Everton'],
            'Arsenal' => ['Tottenham']
        ];

        foreach ($importantMatches as $team => $rivals) {
            if (stripos($prediction['match'], $team) !== false) {
                foreach ($rivals as $rival) {
                    if (stripos($prediction['match'], $rival) !== false) {
                        $score += 2;
                        break 2;
                    }
                }
            }
        }

        return $score;
    }

    protected function getOddsForTip($option, $match, $date)
    {
        try {
            // Use FlashscoreScraper instead of OddsAPI
            $flashscoreScraper = app(\App\Services\FlashscoreScraper::class);
            $odds = $flashscoreScraper->getMatchOdds($match, $option);
            
            if (!empty($odds)) {
                return $odds;
            }
            
            // If Flashscore fails, return N/A
            return 'N/A';
            
        } catch (\Exception $e) {
            Log::error('Error fetching odds: ' . $e->getMessage(), [
                'match' => $match,
                'date' => $date,
                'option' => $option
            ]);
            return 'N/A';
        }
    }

    protected function showHeader()
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                SAVE PREDICTIONS COMMAND                     ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    protected function showOptions($date, $league, $country, $force, $dryRun)
    {
        $this->info('📋 Options:');
        $this->line('----------------------------------------');
        $this->line('Date: ' . ($date ?: 'Not specified'));
        $this->line('League: ' . ($league ?: 'Not specified'));
        $this->line('Country: ' . ($country ?: 'Not specified'));
        $this->line('Force Update: ' . ($force ? 'Yes' : 'No'));
        $this->line('Dry Run: ' . ($dryRun ? 'Yes' : 'No'));
        $this->newLine();
    }

    protected function showPredictionsSummary($predictions)
    {
        $this->info('📊 Predictions Summary:');
        $this->line('----------------------------------------');

        // Group by date
        $byDate = collect($predictions)->groupBy(function ($prediction) {
            return Carbon::parse($prediction['date'])->format('Y-m-d');
        });

        foreach ($byDate as $date => $datePredictions) {
            $this->line("Date: {$date}");
            $this->line("Matches: " . count($datePredictions));
            
            // Group by country
            $byCountry = $datePredictions->groupBy('country');
            foreach ($byCountry as $country => $countryPredictions) {
                $this->line("  {$country}: " . count($countryPredictions) . " matches");
            }
            $this->newLine();
        }
    }

    protected function showResults($result)
    {
        $this->newLine();
        $this->info('✅ Prediction saving completed:');
        $this->line('----------------------------------------');
        $this->line('Saved: ' . $result['saved']);
        $this->line('Skipped: ' . $result['skipped']);
        $this->line('Errors: ' . $result['errors']);
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

    protected function showError(\Exception $e)
    {
        $this->newLine();
        $this->error('╔════════════════════════════════════════════════════════════╗');
        $this->error('║                      ERROR OCCURRED                        ║');
        $this->error('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();
        $this->error('Error: ' . $e->getMessage());
        Log::error('Error in SavePredictions command: ' . $e->getMessage(), [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
} 