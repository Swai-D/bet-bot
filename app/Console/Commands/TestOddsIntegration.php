<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ApiFootballService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TestOddsIntegration extends Command
{
    protected $signature = 'odds:test';
    protected $description = 'Test odds integration with API-FOOTBALL';

    protected $apiService;
    protected $timezone = 'Africa/Dar_es_Salaam';
    protected $apiKey;
    protected $currentSeason;
    protected $oddsApiKey;

    // Add popular leagues
    protected $leagues = [
        78 => 'Bundesliga',
        39 => 'Premier League',
        140 => 'La Liga',
        135 => 'Serie A',
        61 => 'Ligue 1',
        2 => 'UEFA Champions League',
        3 => 'UEFA Europa League',
        848 => 'Tanzania Premier League'
    ];

    public function __construct(ApiFootballService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
        $this->apiKey = config('services.api_football.key');
        $this->oddsApiKey = config('services.odds_api.key');
        
        // Set season to 2023 for free plan
        $this->currentSeason = 2023;
    }

    public function handle()
    {
        $this->info('Testing odds integration with actual matches...');

        // Debug: Print API key and season
        $this->info("API Key: " . substr($this->apiKey, 0, 5) . '...');
        $this->info("Current Season: {$this->currentSeason}");

        // Get today's date in EAT
        $today = Carbon::now($this->timezone);
        $allowedStartDate = Carbon::parse('2025-05-18');
        $allowedEndDate = Carbon::parse('2025-05-20');

        // Check if today is within allowed range
        if ($today->lt($allowedStartDate)) {
            $this->info("Today's date is before allowed range, using start date: {$allowedStartDate->format('Y-m-d')}");
            $testDate = $allowedStartDate->format('Y-m-d');
        } elseif ($today->gt($allowedEndDate)) {
            $this->info("Today's date is after allowed range, using end date: {$allowedEndDate->format('Y-m-d')}");
            $testDate = $allowedEndDate->format('Y-m-d');
        } else {
            $testDate = $today->format('Y-m-d');
        }
        
        $this->info("Testing with date: {$testDate}");
        $this->info("Using season: {$this->currentSeason}/" . ($this->currentSeason + 1));

        // Get actual matches from database
        $this->info("\nFetching actual matches from database...");
        $matches = \App\Models\Prediction::whereDate('date', $testDate)->get();
        
        if ($matches->isEmpty()) {
            $this->error('No matches found in database for ' . $testDate);
            return;
        }

        // Test each match
        foreach ($matches as $match) {
            $this->info("\nTesting match: {$match->match}");
            
            // Try API-FOOTBALL first
            $fixture = $this->getSingleFixture($testDate);
            
            if (empty($fixture)) {
                $this->info("No fixture found from API-FOOTBALL, trying OddsAPI...");
                
                // Try OddsAPI with actual match
                $odds = $this->fetchOddsFromOddsAPI([
                    'teams' => [
                        'home' => ['name' => explode(' vs ', $match->match)[0]],
                        'away' => ['name' => explode(' vs ', $match->match)[1]]
                    ]
                ]);
                
                if (!empty($odds)) {
                    $this->info("\nSuccessfully fetched odds from OddsAPI:");
                    $this->line("Home Win: " . ($odds['1'] ?? 'N/A'));
                    $this->line("Draw: " . ($odds['X'] ?? 'N/A'));
                    $this->line("Away Win: " . ($odds['2'] ?? 'N/A'));
                } else {
                    $this->error('No odds found from OddsAPI');
                }
            } else {
                // Analyze the fixture
                $this->analyzeFixture($fixture);
            }
        }
    }

    protected function getActualMatches()
    {
        try {
            // Get matches from predictions:show
            $response = Http::get('http://localhost:8000/api/predictions/show');
            
            if (!$response->successful()) {
                $this->error('Failed to fetch matches from predictions:show: ' . $response->status());
                return [];
            }

            $data = $response->json();
            
            if (empty($data['matches'])) {
                $this->error('No matches found in predictions:show response');
                return [];
            }

            return $data['matches'];

        } catch (\Exception $e) {
            $this->error('Error fetching matches from predictions:show: ' . $e->getMessage());
            return [];
        }
    }

    protected function getSingleFixture($date)
    {
        $this->info("Fetching fixtures for date: {$date}");
        
        try {
            // Try each league until we find a fixture
            foreach ($this->leagues as $leagueId => $leagueName) {
                $this->info("Trying {$leagueName}...");
                
                // Add delay to avoid rate limit
                sleep(6); // Wait 6 seconds between requests
                
                $response = Http::withHeaders([
                    'x-rapidapi-key' => $this->apiKey,
                    'x-rapidapi-host' => 'v3.football.api-sports.io'
                ])->get('https://v3.football.api-sports.io/fixtures', [
                    'date' => $date,
                    'league' => $leagueId,
                    'season' => $this->currentSeason
                ]);

                // Debug: Print API response
                $this->info("API Response Status: " . $response->status());
                $this->info("API Response Body: " . $response->body());

                if (!$response->successful()) {
                    $this->error("Failed to fetch fixtures for {$leagueName}: " . $response->status());
                    continue;
                }

                $data = $response->json();
                
                // Check for rate limit error
                if (isset($data['errors']['rateLimit'])) {
                    $this->error("Rate limit reached. Waiting 60 seconds...");
                    sleep(60); // Wait 60 seconds before continuing
                    continue;
                }

                // Check for date range error
                if (isset($data['errors']['plan'])) {
                    $this->error("Date range error: " . $data['errors']['plan']);
                    return null;
                }

                if (isset($data['response']) && !empty($data['response'])) {
                    // Get the first fixture
                    $fixture = $data['response'][0];
                    $this->info("Found match in {$leagueName}: " . $fixture['teams']['home']['name'] . ' vs ' . $fixture['teams']['away']['name']);
                    return $fixture;
                }
            }

            $this->error('No fixtures found for ' . $date);
            return null;

        } catch (\Exception $e) {
            $this->error('Error fetching fixtures: ' . $e->getMessage());
            return null;
        }
    }

    protected function analyzeFixture($fixture)
    {
        $this->info("\nAnalyzing fixture details:");
        $this->line("----------------------------------------");

        // Basic match info
        $this->info("\n1. Basic Match Information:");
        $this->line("Match: " . $fixture['teams']['home']['name'] . ' vs ' . $fixture['teams']['away']['name']);
        $this->line("Fixture ID: " . $fixture['fixture']['id']);
        $this->line("League: " . $fixture['league']['name']);
        $this->line("Round: " . $fixture['league']['round']);
        
        // Match time
        $matchTime = Carbon::parse($fixture['fixture']['date'])
            ->setTimezone($this->timezone)
            ->format('Y-m-d H:i:s');
        $this->line("Match Time (EAT): " . $matchTime);

        // Get odds for this specific fixture from API-FOOTBALL
        $this->info("\n2. Fetching Odds Information from API-FOOTBALL:");
        $odds = $this->apiService->getMatchOdds(
            $fixture['teams']['home']['name'] . ' vs ' . $fixture['teams']['away']['name'],
            $matchTime,
            strtolower($fixture['league']['country']),
            $fixture['fixture']['id']
        );

        // If odds are not available, fallback to OddsAPI
        if (empty($odds) || !isset($odds['1']) || !isset($odds['X']) || !isset($odds['2'])) {
            $this->info("Odds not found in API-FOOTBALL, attempting to fetch from OddsAPI...");
            $odds = $this->fetchOddsFromOddsAPI($fixture);
        }

        $this->line("Home Win: " . ($odds['1'] ?? 'N/A'));
        $this->line("Draw: " . ($odds['X'] ?? 'N/A'));
        $this->line("Away Win: " . ($odds['2'] ?? 'N/A'));

        // Team information
        $this->info("\n3. Team Information:");
        $this->line("Home Team ID: " . $fixture['teams']['home']['id']);
        $this->line("Away Team ID: " . $fixture['teams']['away']['id']);

        // Venue information
        $this->info("\n4. Venue Information:");
        $this->line("Venue: " . ($fixture['fixture']['venue']['name'] ?? 'N/A'));
        $this->line("City: " . ($fixture['fixture']['venue']['city'] ?? 'N/A'));

        // Status information
        $this->info("\n5. Match Status:");
        $this->line("Status: " . $fixture['fixture']['status']['long']);
        $this->line("Elapsed: " . ($fixture['fixture']['status']['elapsed'] ?? 'N/A'));

        $this->line("\n----------------------------------------");
        $this->info("\nThis is all the information we get from the APIs for a single match.");
        $this->info("We can use this to understand what data we need to store and display.");
    }

    protected function fetchOddsFromOddsAPI($fixture)
    {
        try {
            $this->info("Fetching odds from OddsAPI...");
            
            // Format match name for OddsAPI
            $homeTeam = $fixture['teams']['home']['name'];
            $awayTeam = $fixture['teams']['away']['name'];
            
            // Make request to OddsAPI
            $response = Http::withHeaders([
                'x-api-key' => $this->oddsApiKey
            ])->get('https://api.the-odds-api.com/v4/sports/soccer/odds', [
                'apiKey' => $this->oddsApiKey,
                'regions' => 'eu',
                'markets' => 'h2h',
                'oddsFormat' => 'decimal'
            ]);

            if (!$response->successful()) {
                $this->error('Failed to fetch odds from OddsAPI: ' . $response->status());
                return [];
            }

            $data = $response->json();
            
            // Find matching game
            foreach ($data as $game) {
                if ($game['home_team'] === $homeTeam && $game['away_team'] === $awayTeam) {
                    // Get odds from first bookmaker
                    if (isset($game['bookmakers'][0]['markets'][0]['outcomes'])) {
                        $outcomes = $game['bookmakers'][0]['markets'][0]['outcomes'];
                        return [
                            '1' => $this->findOddsForTeam($outcomes, $homeTeam),
                            'X' => $this->findOddsForTeam($outcomes, 'Draw'),
                            '2' => $this->findOddsForTeam($outcomes, $awayTeam)
                        ];
                    }
                }
            }

            $this->error('No matching odds found in OddsAPI');
            return [];

        } catch (\Exception $e) {
            $this->error('Error fetching odds from OddsAPI: ' . $e->getMessage());
            return [];
        }
    }

    protected function findOddsForTeam($outcomes, $teamName)
    {
        foreach ($outcomes as $outcome) {
            if ($outcome['name'] === $teamName) {
                return $outcome['price'];
            }
        }
        return 'N/A';
    }
} 