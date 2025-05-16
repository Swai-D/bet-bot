<?php

namespace App\Http\Controllers;

use App\Services\BettingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BettingController extends Controller
{
    protected $bettingService;

    public function __construct(BettingService $bettingService)
    {
        $this->bettingService = $bettingService;
    }

    public function getMatches(): JsonResponse
    {
        try {
            $matches = $this->bettingService->scrapeMatches();
            return response()->json([
                'success' => true,
                'data' => $matches
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function placeBet(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'stake' => 'required|numeric|min:100',
                'min_odds' => 'required|numeric|min:3'
            ]);

            // Scrape and select matches
            $matches = $this->bettingService->scrapeMatches();
            $betData = $this->bettingService->selectMatches(
                $matches,
                $request->input('min_odds', 3.00)
            );

            // Override stake from request
            $betData['stake'] = $request->input('stake');

            // Place the bet
            $result = $this->bettingService->placeBet($betData);

            return response()->json([
                'success' => true,
                'message' => 'Bet placed successfully',
                'data' => [
                    'stake' => $betData['stake'],
                    'tips' => $betData['tips'],
                    'result' => $result
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 