<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Prediction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BettingStrategyService
{
    protected $strategy;
    protected $adibetScraper;
    protected $betpawaBot;

    public function __construct()
    {
        $this->strategy = Cache::get('betting_strategy', $this->getDefaultStrategy());
    }

    public function getDefaultStrategy()
    {
        return [
            'minOdds' => 1.5,
            'maxOdds' => 3.0,
            'baseStake' => 1000,
            'confidenceThreshold' => 'medium',
            'betTypes' => [
                'homeWin' => true,
                'draw' => true,
                'awayWin' => true,
                'over2_5' => true
            ]
        ];
    }

    public function updateStrategy(array $strategy)
    {
        $this->strategy = array_merge($this->strategy, $strategy);
        Cache::put('betting_strategy', $this->strategy, now()->addDay());
        return $this->strategy;
    }

    public function analyzePrediction(Prediction $prediction)
    {
        // Check if prediction meets basic criteria
        if (!$this->meetsBasicCriteria($prediction)) {
            return false;
        }

        // Calculate confidence score
        $confidenceScore = $this->calculateConfidenceScore($prediction);

        // Check if confidence meets threshold
        if (!$this->meetsConfidenceThreshold($confidenceScore)) {
            return false;
        }

        // Calculate optimal stake
        $stake = $this->calculateOptimalStake($prediction, $confidenceScore);

        return [
            'should_bet' => true,
            'stake' => $stake,
            'confidence_score' => $confidenceScore
        ];
    }

    protected function meetsBasicCriteria(Prediction $prediction)
    {
        // Check odds range
        if ($prediction->odds < $this->strategy['minOdds'] || 
            $prediction->odds > $this->strategy['maxOdds']) {
            return false;
        }

        // Check bet type
        $betType = $this->getBetType($prediction->prediction);
        if (!isset($this->strategy['betTypes'][$betType]) || 
            !$this->strategy['betTypes'][$betType]) {
            return false;
        }

        return true;
    }

    protected function calculateConfidenceScore(Prediction $prediction)
    {
        $score = 0;

        // Base score from prediction confidence
        $score += $this->getConfidenceValue($prediction->confidence);

        // Adjust for odds value
        $score += $this->calculateOddsValue($prediction->odds);

        // Adjust for historical performance
        $score += $this->calculateHistoricalPerformance($prediction);

        return $score;
    }

    protected function meetsConfidenceThreshold($confidenceScore)
    {
        $thresholds = [
            'high' => 8,
            'medium' => 6,
            'low' => 4
        ];

        return $confidenceScore >= $thresholds[$this->strategy['confidenceThreshold']];
    }

    protected function calculateOptimalStake(Prediction $prediction, $confidenceScore)
    {
        $baseStake = $this->strategy['baseStake'];
        
        // Adjust stake based on confidence score
        $confidenceMultiplier = $confidenceScore / 10;
        
        // Adjust stake based on odds
        $oddsMultiplier = 1 / $prediction->odds;
        
        // Calculate final stake
        $stake = $baseStake * $confidenceMultiplier * $oddsMultiplier;
        
        // Round to nearest 100
        return round($stake / 100) * 100;
    }

    protected function getBetType($prediction)
    {
        $types = [
            '1' => 'homeWin',
            'X' => 'draw',
            '2' => 'awayWin',
            '+2.5' => 'over2_5'
        ];

        return $types[$prediction] ?? null;
    }

    protected function getConfidenceValue($confidence)
    {
        $values = [
            'high' => 5,
            'medium' => 3,
            'low' => 1
        ];

        return $values[$confidence] ?? 0;
    }

    protected function calculateOddsValue($odds)
    {
        // Higher odds get higher value, but with diminishing returns
        return min(5, ($odds - 1) * 2);
    }

    protected function calculateHistoricalPerformance(Prediction $prediction)
    {
        // Get historical performance for similar predictions
        $similarPredictions = Prediction::where('prediction', $prediction->prediction)
            ->where('odds', '>=', $prediction->odds * 0.9)
            ->where('odds', '<=', $prediction->odds * 1.1)
            ->where('created_at', '>=', now()->subDays(30))
            ->get();

        if ($similarPredictions->isEmpty()) {
            return 0;
        }

        $successRate = $similarPredictions->filter(function ($p) {
            return $p->result === 'win';
        })->count() / $similarPredictions->count();

        return $successRate * 5; // Scale to 0-5
    }

    public function getStats()
    {
        return [
            'activeBets' => Bet::where('status', 'active')->count(),
            'successRate' => $this->calculateSuccessRate(),
            'totalProfit' => $this->calculateTotalProfit(),
            'nextBetTime' => $this->getNextBetTime()
        ];
    }

    protected function calculateSuccessRate()
    {
        $totalBets = Bet::where('status', '!=', 'active')->count();
        if ($totalBets === 0) return 0;

        $winningBets = Bet::where('status', 'win')->count();
        return round(($winningBets / $totalBets) * 100);
    }

    protected function calculateTotalProfit()
    {
        return Bet::where('status', '!=', 'active')
            ->sum('profit');
    }

    protected function getNextBetTime()
    {
        $nextPrediction = Prediction::where('match_time', '>', now())
            ->orderBy('match_time')
            ->first();

        return $nextPrediction ? $nextPrediction->match_time->format('H:i') : null;
    }
} 