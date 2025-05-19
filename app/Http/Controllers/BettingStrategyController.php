<?php

namespace App\Http\Controllers;

use App\Services\BettingStrategyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BettingStrategyController extends Controller
{
    protected $strategyService;

    public function __construct(BettingStrategyService $strategyService)
    {
        $this->strategyService = $strategyService;
    }

    public function getStrategy(): JsonResponse
    {
        return response()->json([
            'strategy' => $this->strategyService->getDefaultStrategy()
        ]);
    }

    public function updateStrategy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'minOdds' => 'required|numeric|min:1.01',
            'maxOdds' => 'required|numeric|min:1.01|gt:minOdds',
            'baseStake' => 'required|numeric|min:100',
            'confidenceThreshold' => 'required|in:high,medium,low',
            'betTypes' => 'required|array',
            'betTypes.homeWin' => 'required|boolean',
            'betTypes.draw' => 'required|boolean',
            'betTypes.awayWin' => 'required|boolean',
            'betTypes.over2_5' => 'required|boolean'
        ]);

        $strategy = $this->strategyService->updateStrategy($validated);

        return response()->json([
            'message' => 'Strategy updated successfully',
            'strategy' => $strategy
        ]);
    }

    public function getStats(): JsonResponse
    {
        return response()->json($this->strategyService->getStats());
    }

    public function startAutomation(): JsonResponse
    {
        // Start the automation process
        // This would typically involve starting a background job
        return response()->json([
            'message' => 'Automation started successfully'
        ]);
    }

    public function stopAutomation(): JsonResponse
    {
        // Stop the automation process
        // This would typically involve stopping the background job
        return response()->json([
            'message' => 'Automation stopped successfully'
        ]);
    }
} 