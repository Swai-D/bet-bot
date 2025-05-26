<?php

namespace App\Services;

use App\Models\Prediction;
use Illuminate\Support\Facades\Log;

class PredictionService
{
    public function processPredictions($predictions)
    {
        return array_map(function($prediction) {
            // Ensure all required fields exist
            $prediction = array_merge([
                'match' => '',
                'home_team' => '',
                'away_team' => '',
                'country' => '',
                'date' => '',
                'time' => '',
                'league' => '',
                'tips' => []
            ], $prediction);

            // Process tips
            if (isset($prediction['tips']) && is_array($prediction['tips'])) {
                $prediction['tips'] = array_map(function($tip) {
                    return array_merge([
                        'prediction' => '',
                        'odds' => '',
                        'confidence' => 'Medium'
                    ], $tip);
                }, $prediction['tips']);
            }

            return $prediction;
        }, $predictions);
    }

    public function savePredictions($predictions)
    {
        $saved = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($predictions as $prediction) {
            try {
                // Check if prediction already exists
                $exists = Prediction::where('match', $prediction['match'])
                    ->where('date', $prediction['date'])
                    ->where('time', $prediction['time'])
                    ->exists();

                if ($exists) {
                    $skipped++;
                    continue;
                }

                // Create new prediction
                $predictionModel = new Prediction();
                $predictionModel->match = $prediction['match'];
                $predictionModel->home_team = $prediction['home_team'];
                $predictionModel->away_team = $prediction['away_team'];
                $predictionModel->country = $prediction['country'];
                $predictionModel->date = $prediction['date'];
                $predictionModel->time = $prediction['time'];
                $predictionModel->league = $prediction['league'];
                $predictionModel->tips = json_encode($prediction['tips']);
                $predictionModel->save();

                $saved++;
            } catch (\Exception $e) {
                $errors++;
                Log::error('Error saving prediction: ' . $e->getMessage());
            }
        }

        return [
            'saved' => $saved,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }
} 