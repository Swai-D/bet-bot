<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\AdibetScraper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'match' => 'required|string',
                'country' => 'required|string',
                'league' => 'nullable|string',
                'date' => 'required|date',
                'time' => 'required|string',
                'tips' => 'required|array',
                'source' => 'required|in:adibet,sportytrader',
                'raw_data' => 'nullable|array'
            ]);

            // Generate unique match_id
            $matchId = md5($data['match'] . $data['date'] . $data['time']);

            // Check if prediction already exists
            $existingPrediction = Prediction::where('match_id', $matchId)->first();

            if ($existingPrediction) {
                // Update existing prediction
                $existingPrediction->update([
                    'match' => $data['match'],
                    'country' => $data['country'],
                    'league' => $data['league'] ?? 'Unknown League',
                    'match_date' => $data['date'],
                    'match_time' => $data['time'],
                    'tips' => $data['tips'],
                    'raw_data' => $data['raw_data'] ?? null,
                    'source' => $data['source']
                ]);

                return response()->json([
                    'message' => 'Prediction updated successfully',
                    'prediction' => $existingPrediction
                ], 200);
            }

            // Create new prediction
            $prediction = Prediction::create([
                'match_id' => $matchId,
                'match' => $data['match'],
                'country' => $data['country'],
                'league' => $data['league'] ?? 'Unknown League',
                'match_date' => $data['date'],
                'match_time' => $data['time'],
                'tips' => $data['tips'],
                'raw_data' => $data['raw_data'] ?? null,
                'source' => $data['source']
            ]);

            return response()->json([
                'message' => 'Prediction created successfully',
                'prediction' => $prediction
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error storing prediction: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to store prediction',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getLatestPredictions()
    {
        try {
            $predictions = Prediction::where('date', '>=', now())
                ->orderBy('date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $predictions
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching predictions: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to fetch predictions',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 