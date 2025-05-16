<?php

namespace App\Services;

use App\Models\Bet;
use Illuminate\Support\Facades\Log;

class BetService
{
    protected $betpawa;

    public function __construct(BetpawaAutomation $betpawa)
    {
        $this->betpawa = $betpawa;
    }

    public function placeBet($matchName, $oddType, $amount)
    {
        try {
            // Search for match
            $matches = $this->betpawa->searchMatch($matchName);
            
            if (empty($matches)) {
                Log::error("No matches found for: {$matchName}");
                return [
                    'success' => false,
                    'message' => 'No matches found'
                ];
            }

            // Find the match with the specified odd type
            $match = collect($matches)->first(function($match) use ($oddType) {
                return $match['type'] === $oddType;
            });

            if (!$match) {
                Log::error("No match found with odd type: {$oddType}");
                return [
                    'success' => false,
                    'message' => 'No match found with specified odd type'
                ];
            }

            // Calculate potential win
            $potentialWin = $amount * $match['odds'];

            // Create bet record
            $bet = Bet::create([
                'match_id' => $match['id'],
                'match_name' => $matchName,
                'odd_type' => $oddType,
                'odds' => $match['odds'],
                'amount' => $amount,
                'potential_win' => $potentialWin,
                'placed_at' => now()
            ]);

            // Place bet on Betpawa
            $result = $this->betpawa->placeBet($match['id'], $oddType, $amount);

            if (!$result['success']) {
                $bet->delete();
                return $result;
            }

            return [
                'success' => true,
                'message' => 'Bet placed successfully',
                'bet' => $bet
            ];

        } catch (\Exception $e) {
            Log::error('Error placing bet: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function checkBetStatus(Bet $bet)
    {
        try {
            // Get match result from Betpawa
            $result = $this->betpawa->getMatchResult($bet->match_id);

            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => $result['message']
                ];
            }

            // Update bet status
            if ($result['won']) {
                $bet->markAsWon($result['win_amount']);
            } else {
                $bet->markAsLost();
            }

            return [
                'success' => true,
                'message' => 'Bet status updated',
                'bet' => $bet
            ];

        } catch (\Exception $e) {
            Log::error('Error checking bet status: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getPendingBets()
    {
        return Bet::where('status', 'pending')->get();
    }

    public function getWonBets()
    {
        return Bet::where('status', 'won')->get();
    }

    public function getLostBets()
    {
        return Bet::where('status', 'lost')->get();
    }

    public function getTotalWinnings()
    {
        return Bet::where('status', 'won')->sum('actual_win');
    }

    public function getTotalLosses()
    {
        return Bet::where('status', 'lost')->sum('amount');
    }

    public function getProfit()
    {
        return $this->getTotalWinnings() - $this->getTotalLosses();
    }
} 