<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Prediction;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class AdibetScraper
{
    protected $client;
    protected $baseUrl = 'https://www.adibet.com';
    protected $maxRetries = 3;
    protected $retryDelay = 5; // seconds

    // Define leagues by tier
    protected $leagueTiers = [
        'top' => [
            'England' => ['Premier League', 'Championship'],
            'Germany' => ['Bundesliga', '2. Bundesliga'],
            'Spain' => ['La Liga', 'La Liga 2'],
            'Italy' => ['Serie A', 'Serie B'],
            'France' => ['Ligue 1', 'Ligue 2']
        ],
        'moderate' => [
            'Netherlands' => ['Eredivisie'],
            'Portugal' => ['Primeira Liga'],
            'Turkey' => ['Super Lig'],
            'Belgium' => ['Pro League'],
            'Scotland' => ['Premiership']
        ]
    ];

    // Define league scores (higher score = better league)
    protected $leagueScores = [
        'top' => [
            'England' => 5,    // Premier League is the best
            'Germany' => 4,    // Bundesliga is second
            'Spain' => 4,      // La Liga is also top tier
            'Italy' => 3,      // Serie A is strong
            'France' => 3      // Ligue 1 is good
        ],
        'moderate' => [
            'Netherlands' => 2,
            'Portugal' => 2,
            'Turkey' => 2,
            'Belgium' => 2,
            'Scotland' => 2
        ]
    ];

    // Define prediction types
    protected $predictionTypes = [
        '1' => 'Home Win',
        'X' => 'Draw',
        '2' => 'Away Win',
        '-2.5' => 'Under 2.5',
        '+2.5' => 'Over 2.5',
        'GG' => 'Both Teams to Score',
        'NG' => 'No Goals',
        '1X' => 'Home Win or Draw',
        'X2' => 'Draw or Away Win',
        '12' => 'Home Win or Away Win'
    ];

    public function __construct()
    {
        $this->client = new Client([
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
                'Connection' => 'keep-alive',
                'Upgrade-Insecure-Requests' => '1'
            ],
            'timeout' => 30,
            'verify' => false // Disable SSL verification for development
        ]);
    }

    public function fetchPredictions()
    {
        $retries = 0;
        while ($retries < $this->maxRetries) {
            try {
                Log::info('Starting to fetch predictions from Adibet (Attempt ' . ($retries + 1) . ')');

                $response = $this->client->get($this->baseUrl);
                $html = $response->getBody()->getContents();
                
                if (empty($html)) {
                    throw new \Exception('Received empty response from Adibet');
                }

                // Save HTML for debugging
                $this->saveHtmlForDebugging($html);

                Log::info('Successfully fetched HTML from Adibet');
                
                $predictions = $this->parsePredictions($html);
                
                if (empty($predictions)) {
                    throw new \Exception('No predictions found in the parsed data');
                }
                
                Log::info('Successfully parsed ' . count($predictions) . ' predictions');
                return $predictions;
                
            } catch (\Exception $e) {
                $retries++;
                Log::error('Error fetching predictions (Attempt ' . $retries . '): ' . $e->getMessage());
                
                if ($retries < $this->maxRetries) {
                    Log::info('Retrying in ' . $this->retryDelay . ' seconds...');
                    sleep($this->retryDelay);
                } else {
                    Log::error('Failed to fetch predictions after ' . $this->maxRetries . ' attempts');
                    throw $e;
                }
            }
        }
    }

    protected function saveHtmlForDebugging($html)
    {
        $filename = storage_path('logs/adibet_' . now()->format('Y-m-d_H-i-s') . '.html');
        file_put_contents($filename, $html);
        Log::info('Saved HTML for debugging: ' . $filename);
    }

    protected function parsePredictions($html)
    {
        $crawler = new Crawler($html);
        $predictions = [];
        $processedMatches = []; // Track processed matches to avoid duplicates
        
        // Format today's date in Adibet format (e.g. "16 - 05 - 2025")
        $today = Carbon::now()->format('d - m - Y');
        $currentDate = null;

        try {
        // First find all date headers
            $dateHeaders = $crawler->filter('tr:contains("' . $today . '")')->each(function (Crawler $node) {
            return trim($node->text());
        });

            Log::info('Found date headers: ' . json_encode($dateHeaders));

            // Then process each table row
            $crawler->filter('tr')->each(function (Crawler $row) use (&$predictions, &$currentDate, &$processedMatches) {
                $rowText = trim($row->text());
                
                // Check if this is a date header
                if (preg_match('/(\d+)\s*-\s*(\d+)\s*-\s*(\d+)/', $rowText, $matches)) {
                    $currentDate = Carbon::createFromFormat('d - m - Y', $matches[0])->format('Y-m-d');
                    Log::info("Processing matches for date: {$currentDate}");
                return;
            }

                // Skip if no date found yet
                if (!$currentDate) {
                return;
            }

                // Skip if this is a header row or social media links
                if (str_contains($rowText, 'Link to') || str_contains($rowText, 'Telegram')) {
                        return;
                    }

                // Parse match data
                $columns = $row->filter('td')->each(function (Crawler $cell) {
                    return trim($cell->text());
                });

                if (count($columns) < 3) {
                    return; // Skip invalid rows
                }

                // Get country from image alt text
                $country = $row->filter('td')->eq(0)->filter('img')->attr('alt');
                if (!$country) {
                    return; // Skip if no country found
                }

                $matchText = $columns[1];
                $teams = explode(' - ', $matchText);
                
                if (count($teams) !== 2) {
                    return; // Skip invalid team format
                }

                $teamHome = trim($teams[0]);
                $teamAway = trim($teams[1]);
                $matchId = Str::slug("{$teamHome}-vs-{$teamAway}-{$currentDate}");
                    
                    // Skip if we've already processed this match
                    if (in_array($matchId, $processedMatches)) {
                        return;
                    }
                    
                    $processedMatches[] = $matchId;

                // Get predictions (highlighted ones)
                $tips = [];
                for ($i = 2; $i < count($columns); $i++) {
                    $tip = trim($columns[$i]);
                    if (isset($this->predictionTypes[$tip])) {
                        // Check if this cell has a background color (highlighted)
                        $isHighlighted = $row->filter('td')->eq($i)->attr('bgcolor') === '#272727' && 
                                       $row->filter('td')->eq($i)->filter('font')->attr('color') === '#D5B438';
                        
                            $tips[] = [
                                'option' => $tip,
                            'name' => $this->predictionTypes[$tip],
                            'selected' => $isHighlighted // This is a highlighted prediction
                            ];
                        }
                }

                    if (empty($tips)) {
                        return;
                    }

                // Calculate match score
                $score = $this->calculateMatchScore($country, $matchText, count($tips));
                    
                    $predictions[] = [
                        'match_id' => $matchId,
                    'match' => "{$teamHome} vs {$teamAway}",
                        'country' => $country,
                    'league' => $this->getLeagueName($country, $teamHome, $teamAway),
                    'date' => $currentDate,
                        'tips' => $tips,
                    'score' => $score,
                        'raw_data' => json_encode([
                            'html' => $row->outerHtml(),
                            'timestamp' => now()->toIso8601String()
                        ])
                    ];

                Log::info("Successfully parsed match: {$teamHome} vs {$teamAway} with score {$score}");
            });

            Log::info('Successfully parsed ' . count($predictions) . ' predictions');
            return $predictions;
                } catch (\Exception $e) {
            Log::error('Error parsing predictions: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function getLeagueName($country, $teamHome, $teamAway)
    {
        // Map teams to leagues
        $teamLeagues = [
            'England' => [
                'Premier League' => ['Manchester United', 'Manchester City', 'Liverpool', 'Arsenal', 'Chelsea', 'Tottenham', 'Crystal Palace'],
                'Championship' => ['Leeds', 'Leicester', 'Southampton', 'Norwich']
            ],
            'Germany' => [
                'Bundesliga' => ['Bayern Munich', 'Borussia Dortmund', 'RB Leipzig', 'Bayer Leverkusen', 'Wolfsburg', 'Hoffenheim', 'B. Monchengladbach'],
                '2. Bundesliga' => ['Holstein Kiel', 'St. Pauli', 'Hamburg']
            ],
            'Spain' => [
                'La Liga' => ['Barcelona', 'Real Madrid', 'Atletico Madrid', 'Sevilla', 'Valencia'],
                'La Liga 2' => ['Almeria', 'Leganes', 'Valladolid']
            ],
            'Italy' => [
                'Serie A' => ['Milan', 'Inter', 'Juventus', 'Napoli', 'Roma', 'Lazio', 'Atalanta', 'Genoa'],
                'Serie B' => ['Parma', 'Brescia', 'Cosenza']
            ],
            'France' => [
                'Ligue 1' => ['PSG', 'Marseille', 'Lyon', 'Monaco', 'Lille', 'Reims', 'Angers'],
                'Ligue 2' => ['Auxerre', 'Bordeaux', 'Grenoble']
            ],
            'Netherlands' => [
                'Eredivisie' => ['Ajax', 'PSV', 'Feyenoord', 'AZ']
            ],
            'Portugal' => [
                'Primeira Liga' => ['Benfica', 'Porto', 'Sporting', 'Braga', 'Vitoria Guimaraes', 'Nacional']
            ],
            'Turkey' => [
                'Super Lig' => ['Galatasaray', 'Fenerbahce', 'Besiktas', 'Trabzonspor']
            ],
            'Belgium' => [
                'Pro League' => ['Antwerp', 'Royale Union SG', 'Club Brugge', 'Anderlecht']
            ],
            'Scotland' => [
                'Premiership' => ['Celtic', 'Rangers', 'Aberdeen', 'Hibernian', 'St. Mirren']
            ]
        ];

        if (isset($teamLeagues[$country])) {
            foreach ($teamLeagues[$country] as $league => $teams) {
                if (in_array($teamHome, $teams) || in_array($teamAway, $teams)) {
                    return $league;
                }
            }
        }

        return 'Unknown League';
    }

    protected function calculateMatchScore($country, $match, $tipCount)
    {
        $score = 0;
        
        // Score based on country/league
        foreach ($this->leagueScores as $tier => $countries) {
            if (isset($countries[$country])) {
                $score += $countries[$country];
                break;
            }
        }

        // Score based on match importance
        if ($this->isImportantMatch($match)) {
            $score += 2;
        }

        // Score based on number of tips
        $score += $tipCount * 0.5;

        return $score;
    }

    protected function isImportantMatch($match)
    {
        // List of important matches/derbies
        $importantMatches = [
            'manchester united vs manchester city',
            'liverpool vs everton',
            'arsenal vs tottenham',
            'barcelona vs real madrid',
            'bayern munich vs borussia dortmund',
            'milan vs inter',
            'psg vs marseille',
            'celtic vs rangers',
            'benfica vs porto',
            'ajax vs psv'
        ];

        return in_array(strtolower($match), $importantMatches);
    }

    public function savePredictions($predictions)
    {
        $today = Carbon::now()->format('Y-m-d');
        $savedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        Log::info("Starting to save " . count($predictions) . " predictions");
        
        foreach ($predictions as $prediction) {
            try {
                // Only save predictions for today and future dates
                if (Carbon::parse($prediction['date'])->lt($today)) {
                    Log::info("Skipping past prediction for match: {$prediction['match']}");
                    $skippedCount++;
                    continue;
                }

                // Create or update the prediction with tips included
                $savedPrediction = Prediction::updateOrCreate(
                    ['match_id' => $prediction['match_id']],
                    [
                        'match' => $prediction['match'],
                        'country' => $prediction['country'],
                        'league' => $prediction['league'] ?? 'Unknown League',
                        'date' => $prediction['date'],
                        'score' => $prediction['score'] ?? 0,
                        'tips' => $prediction['tips'],
                        'raw_data' => $prediction['raw_data'] ?? null
                    ]
                );

                Log::info("Successfully saved prediction for match: {$prediction['match']} with " . count($prediction['tips']) . " tips");
                $savedCount++;
            } catch (\Exception $e) {
                Log::error('Error saving prediction for match ' . ($prediction['match'] ?? 'unknown') . ': ' . $e->getMessage());
                $errorCount++;
            }
        }

        Log::info("Prediction saving completed: {$savedCount} saved, {$skippedCount} skipped, {$errorCount} errors");
        return [
            'saved' => $savedCount,
            'skipped' => $skippedCount,
            'errors' => $errorCount
        ];
    }

    public function getBestMatches($limit = 5)
    {
        return $this->getMatchesByTier('top', $limit);
    }

    public function getModerateMatches($limit = 5)
    {
        return $this->getMatchesByTier('moderate', $limit);
    }

    protected function getMatchesByTier($tier, $limit = 5)
    {
        $predictions = $this->fetchPredictions();
        if (empty($predictions)) {
            return [];
        }

        // Filter predictions by tier and remove duplicates
        $filteredPredictions = collect($predictions)
            ->filter(function ($match) use ($tier) {
                return isset($this->leagueTiers[$tier][$match['country']]);
            })
            ->unique('match_id') // Remove duplicates based on match_id
            ->values(); // Reset array keys

        // Score each match based on criteria
        $scoredMatches = $filteredPredictions->map(function ($match) use ($tier) {
            $score = 0;
            
            // Score based on country/league
            if (isset($this->leagueScores[$tier][$match['country']])) {
                $score += $this->leagueScores[$tier][$match['country']];
            }

            // Score based on number of tips (more tips = more confidence)
            $score += count($match['tips']) * 0.5;

            // Score based on match importance (derbies, top teams)
            if ($this->isImportantMatch($match['match'])) {
                $score += 2;
            }

            return [
                'match' => $match,
                'score' => $score
            ];
        })
        ->sortByDesc('score')
        ->take($limit)
        ->map(function ($item) {
            return $item['match'];
        });

        return $scoredMatches->toArray();
    }

    public function getRandomPrediction()
    {
        $predictions = $this->fetchPredictions();
        if (empty($predictions)) {
            return null;
        }

        return $predictions[array_rand($predictions)];
    }

    /**
     * Clean up old predictions that are no longer relevant
     * 
     * @param int $daysToKeep Number of days to keep predictions
     * @return int Number of predictions deleted
     */
    public function cleanupOldPredictions($daysToKeep = 7)
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $deletedCount = Prediction::where('date', '<', $cutoffDate)
            ->whereDoesntHave('bets') // Don't delete predictions that have bets
            ->delete();
            
        Log::info("Cleaned up {$deletedCount} old predictions");
        
        return $deletedCount;
    }
} 