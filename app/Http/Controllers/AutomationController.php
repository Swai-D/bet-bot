<?php

namespace App\Http\Controllers;

use App\Services\BetPawaService;
use App\Services\AdibetScraperService;
use App\Services\OddsPortalScraper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\Setting;

class AutomationController extends Controller
{
    private $betPawaService;
    private $adibetScraper;
    private $oddsPortalScraper;

    public function __construct(
        BetPawaService $betPawaService,
        AdibetScraperService $adibetScraper,
        OddsPortalScraper $oddsPortalScraper
    ) {
        $this->betPawaService = $betPawaService;
        $this->adibetScraper = $adibetScraper;
        $this->oddsPortalScraper = $oddsPortalScraper;
    }

    /**
     * Start the automation process
     */
    public function start(Request $request)
    {
        try {
            // Validate session
            if (!$this->betPawaService->isSessionValid()) {
                if (!$this->betPawaService->login()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Failed to login to BetPawa'
                    ], 401);
                }
            }

            // Get matches from Adibet
            $matches = $this->adibetScraper->scrapeMatches();
            if (empty($matches)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matches found from Adibet'
                ], 404);
            }

            // Process each match
            $results = [];
            foreach ($matches as $match) {
                $result = $this->processMatch($match);
                if ($result) {
                    $results[] = $result;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Automation completed',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Automation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Automation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a single match
     */
    private function processMatch($match)
    {
        try {
            // Get odds for the match
            $odds = $this->oddsPortalScraper->getOdds(
                $match['home_team'] . ' vs ' . $match['away_team'],
                '1X2'
            );

            if (!$odds) {
                Log::warning('No odds found for match', [
                    'match' => $match['home_team'] . ' vs ' . $match['away_team']
                ]);
                return null;
            }

            // Calculate match score
            $matchScore = $this->adibetScraper->calculateMatchScore($match);

            // Check if odds are favorable
            if (!$this->oddsPortalScraper->areOddsFavorable($odds)) {
                Log::info('Odds not favorable for match', [
                    'match' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'odds' => $odds,
                    'score' => $matchScore
                ]);
                return null;
            }

            // Determine best bet based on tips and odds
            $bestBet = $this->determineBestBet($match['tips'], $odds);
            if (!$bestBet) {
                return null;
            }

            // Place bet
            $betPlaced = $this->betPawaService->placeBet(
                $match['match_id'],
                $bestBet['selection'],
                $bestBet['stake']
            );

            if ($betPlaced) {
                return [
                    'match' => $match['home_team'] . ' vs ' . $match['away_team'],
                    'selection' => $bestBet['selection'],
                    'odds' => $bestBet['odds'],
                    'stake' => $bestBet['stake'],
                    'score' => $matchScore
                ];
            }

            return null;

        } catch (\Exception $e) {
            Log::error('Failed to process match: ' . $e->getMessage(), [
                'match' => $match
            ]);
            return null;
        }
    }

    /**
     * Determine the best bet based on tips and odds
     */
    private function determineBestBet($tips, $odds)
    {
        $bestBet = null;
        $maxValue = 0;

        // Map tips to odds
        $tipOdds = [
            '1' => $odds['home'] ?? 0,
            'X' => $odds['draw'] ?? 0,
            '2' => $odds['away'] ?? 0
        ];

        // Calculate value for each tip
        foreach ($tips as $tip) {
            if (isset($tipOdds[$tip]) && $tipOdds[$tip] > 0) {
                $value = $tipOdds[$tip] * 0.5; // Base value
                
                // Add bonus for higher odds
                if ($tipOdds[$tip] >= 2.0) {
                    $value *= 1.2;
                }

                if ($value > $maxValue) {
                    $maxValue = $value;
                    $bestBet = [
                        'selection' => $tip,
                        'odds' => $tipOdds[$tip],
                        'stake' => $this->calculateStake($tipOdds[$tip])
                    ];
                }
            }
        }

        return $bestBet;
    }

    /**
     * Calculate stake based on odds
     */
    private function calculateStake($odds)
    {
        // Base stake
        $baseStake = 100;

        // Adjust stake based on odds
        if ($odds >= 3.0) {
            return $baseStake * 0.5; // Lower stake for high odds
        } elseif ($odds >= 2.0) {
            return $baseStake * 0.75; // Medium stake for medium odds
        }

        return $baseStake; // Full stake for low odds
    }

    /**
     * Toggle bot status
     */
    public function toggle()
    {
        try {
            $currentStatus = Cache::get('bot_status', false);
            $newStatus = !$currentStatus;
            
            Cache::put('bot_status', $newStatus, now()->addDay());
            
            if ($newStatus) {
                // Start automation process
                $this->start(new Request());
            }

            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => $newStatus ? 'Bot started' : 'Bot stopped'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle bot: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle bot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stop the bot
     */
    public function stop()
    {
        try {
            Cache::put('bot_status', false, now()->addDay());
            
            return response()->json([
                'success' => true,
                'message' => 'Bot stopped successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to stop bot: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop bot: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get automation status
     */
    public function status()
    {
        return response()->json([
            'session_valid' => true, // TODO: Implement actual session validation
            'remaining_requests' => 100, // TODO: Implement actual request counting
            'last_run' => Setting::first()?->last_run ?? null
        ]);
    }

    public function getStrategy()
    {
        $settings = Setting::first() ?? new Setting();
        return response()->json([
            'strategy' => [
                'minOdds' => $settings->min_odds ?? 1.5,
                'maxOdds' => 3.0,
                'baseStake' => $settings->bet_amount ?? 1000,
                'confidenceThreshold' => 'medium',
                'betTypes' => [
                    'homeWin' => true,
                    'draw' => true,
                    'awayWin' => true,
                    'over2_5' => true
                ]
            ]
        ]);
    }

    public function saveStrategy(Request $request)
    {
        $validated = $request->validate([
            'minOdds' => 'required|numeric|min:1.01|max:100',
            'maxOdds' => 'required|numeric|min:1.01|max:100',
            'baseStake' => 'required|integer|min:100|max:1000000',
            'confidenceThreshold' => 'required|in:high,medium,low',
            'betTypes' => 'required|array',
            'betTypes.homeWin' => 'boolean',
            'betTypes.draw' => 'boolean',
            'betTypes.awayWin' => 'boolean',
            'betTypes.over2_5' => 'boolean'
        ]);

        $settings = Setting::first() ?? new Setting();
        $settings->min_odds = $validated['minOdds'];
        $settings->bet_amount = $validated['baseStake'];
        $settings->save();

        return response()->json(['success' => true]);
    }
} 