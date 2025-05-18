<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AdibetScraper;

class PredictionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prediction::query();

        // Apply best predictions filter (odds >= 2.5)
        if ($request->boolean('best')) {
            $query->where('odds', '>=', 2.5);
        }

        // Apply moderate predictions filter (odds between 1.5 and 2.5)
        if ($request->boolean('moderate')) {
            $query->whereBetween('odds', [1.5, 2.5]);
        }

        // Apply date filter
        if ($request->has('date')) {
            $query->whereDate('date', Carbon::parse($request->date));
        }

        // Apply team filter
        if ($request->has('team')) {
            $query->where('teams', 'like', '%' . $request->team . '%');
        }

        // Apply tip filter
        if ($request->has('tip')) {
            $query->where('tips', 'like', '%' . $request->tip . '%');
        }

        // Get predictions with pagination
        $predictions = $query->latest()->get();

        return response()->json([
            'success' => true,
            'predictions' => $predictions
        ]);
    }

    /**
     * Run the scraper and save predictions
     */
    public function runScraper()
    {
        try {
            $scraper = new AdibetScraper();
            
            // Fetch and save predictions
            $predictions = $scraper->fetchPredictions();
            $result = $scraper->savePredictions($predictions);
            
            // Get latest predictions after saving
            $latestPredictions = Prediction::with('tips')
                ->whereDate('date', '>=', now())
                ->latest()
                ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Successfully scraped and saved predictions',
                'data' => [
                    'saved' => $result['saved'],
                    'skipped' => $result['skipped'],
                    'errors' => $result['errors']
                ],
                'predictions' => $latestPredictions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to scrape predictions: ' . $e->getMessage()
            ], 500);
        }
    }
} 