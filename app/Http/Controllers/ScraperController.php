<?php

namespace App\Http\Controllers;

use App\Services\AdibetScraper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ScraperController extends Controller
{
    protected $scraper;

    public function __construct(AdibetScraper $scraper)
    {
        $this->scraper = $scraper;
    }

    public function run()
    {
        try {
            $predictions = $this->scraper->fetchPredictions();
            
            if (empty($predictions)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No predictions found'
                ], 404);
            }

            // Save predictions to database
            $this->scraper->savePredictions($predictions);

            return response()->json([
                'success' => true,
                'message' => 'Predictions scraped successfully',
                'data' => [
                    'total_predictions' => count($predictions),
                    'timestamp' => now()->toIso8601String()
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Scraper error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to scrape predictions: ' . $e->getMessage()
            ], 500);
        }
    }
} 