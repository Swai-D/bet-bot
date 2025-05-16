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
    protected $maxMatches = 5;

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
        try {
            Log::info('Starting to fetch predictions from Adibet');

            $response = $this->client->get($this->baseUrl);
            $html = $response->getBody()->getContents();
            
            if (empty($html)) {
                throw new \Exception('Received empty response from Adibet');
            }

            Log::info('Successfully fetched HTML from Adibet');
            return $this->parsePredictions($html);
        } catch (\Exception $e) {
            Log::error('Error fetching predictions: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function parsePredictions($html)
    {
        $crawler = new Crawler($html);
        $predictions = [];
        $processedMatches = []; // Track processed matches to avoid duplicates
        
        // Format today's date in Adibet format (e.g. "16 - 05 - 2025")
        $today = Carbon::now()->format('d - m - Y');
        $currentDate = null;
        $isTodaySection = false;

        // First find all date headers
        $dateHeaders = $crawler->filter('font[color="#C0C0C0"]')->each(function (Crawler $node) {
            return trim($node->text());
        });

        // Then process each table
        $crawler->filter('table')->each(function (Crawler $table) use (&$predictions, &$currentDate, &$processedMatches) {
            // Check if this is a date header table
            $dateHeader = $table->filter('font[color="#C0C0C0"]');
            if ($dateHeader->count() > 0) {
                $rawDate = trim($dateHeader->text());
                $dateMatch = preg_match('/(\d+)\s*-\s*(\d+)\s*-\s*(\d+)/', $rawDate, $matches);
                
                if ($dateMatch) {
                    $currentDate = Carbon::createFromFormat('d - m - Y', $matches[0])->format('Y-m-d');
                    Log::info("Processing matches for date: {$currentDate}");
                }
                return;
            }

            // Process match rows
            $table->filter('tr')->each(function (Crawler $row) use (&$predictions, $currentDate, &$processedMatches) {
                if ($row->filter('th')->count() > 0) {
                    return; // Skip header rows
                }

                try {
                    // Get country from image alt
                    $country = $row->filter('img')->attr('alt') ?? 'Unknown';

                    // Get teams from yellow text
                    $teamsText = $row->filter('font[color="#D5B438"]')->text();
                    $teams = explode(' - ', $teamsText);
                    
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

                    // Get tips
                    $tips = [];
                    $row->filter('td[bgcolor="#272727"]')->each(function (Crawler $cell) use (&$tips) {
                        $tip = $cell->filter('font[color="#D5B438"]')->text();
                        if (!empty($tip)) {
                            $tips[] = [
                                'option' => $tip,
                                'odd' => null // We don't have odds from Adibet
                            ];
                        }
                    });

                    if (empty($tips)) {
                        return;
                    }
                    
                    $predictions[] = [
                        'match_id' => $matchId,
                        'match' => "{$teamHome} vs {$teamAway}",
                        'country' => $country,
                        'date' => $currentDate,
                        'tips' => $tips,
                        'raw_data' => json_encode([
                            'html' => $row->outerHtml(),
                            'timestamp' => now()->toIso8601String()
                        ])
                    ];
                } catch (\Exception $e) {
                    Log::error('Error parsing prediction row: ' . $e->getMessage());
                }
            });
        });

        Log::info('Successfully parsed ' . count($predictions) . ' predictions');
        return $predictions;
    }

    public function savePredictions($predictions)
    {
        foreach ($predictions as $prediction) {
            try {
            Prediction::updateOrCreate(
                ['match_id' => $prediction['match_id']],
                $prediction
            );
            } catch (\Exception $e) {
                Log::error('Error saving prediction: ' . $e->getMessage());
            }
        }
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

        return $scoredMatches->values()->all();
    }

    protected function isImportantMatch($match)
    {
        $importantMatches = [
            // Top Tier
            'Manchester' => ['United', 'City'],
            'Arsenal' => ['Tottenham', 'Chelsea'],
            'Liverpool' => ['Manchester', 'Chelsea', 'Arsenal'],
            'Bayern' => ['Dortmund', 'Leipzig'],
            'Dortmund' => ['Schalke'],
            'Barcelona' => ['Real Madrid', 'Atletico'],
            'Real Madrid' => ['Atletico'],
            'Milan' => ['Inter', 'Juventus'],
            'Inter' => ['Juventus'],
            'Roma' => ['Lazio'],
            'PSG' => ['Marseille', 'Lyon'],
            'Marseille' => ['Lyon'],

            // Moderate Tier
            'Ajax' => ['PSV', 'Feyenoord'],
            'Benfica' => ['Porto', 'Sporting'],
            'Galatasaray' => ['Fenerbahce', 'Besiktas'],
            'Anderlecht' => ['Club Brugge'],
            'Celtic' => ['Rangers']
        ];

        foreach ($importantMatches as $team => $rivals) {
            if (stripos($match, $team) !== false) {
                foreach ($rivals as $rival) {
                    if (stripos($match, $rival) !== false) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getRandomPrediction()
    {
        // Get best matches first
        $bestMatches = $this->getBestMatches(5);
        if (empty($bestMatches)) {
            return null;
        }

        // Get a random match from best matches
        $match = collect($bestMatches)->random();
        
        // Get a random tip from the match
        $tip = collect($match['tips'])->random();

        return [
            'match' => $match['match'],
            'country' => $match['country'],
            'tip' => $tip['option']
        ];
    }
} 