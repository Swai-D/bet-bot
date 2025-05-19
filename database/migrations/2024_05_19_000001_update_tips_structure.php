<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Prediction;

return new class extends Migration
{
    public function up()
    {
        // Update existing predictions
        $predictions = Prediction::all();
        
        foreach ($predictions as $prediction) {
            $tips = collect($prediction->tips)->map(function ($tip) {
                if (is_array($tip)) {
                    return [
                        'option' => $tip['option'] ?? $tip,
                        'odd' => 'N/A',
                        'status' => $tip['selected'] ?? false ? 'selected' : 'not selected'
                    ];
                }
                return [
                    'option' => $tip,
                    'odd' => 'N/A',
                    'status' => 'not selected'
                ];
            })->toArray();
            
            $prediction->tips = $tips;
            $prediction->save();
        }
    }

    public function down()
    {
        // Revert changes if needed
        $predictions = Prediction::all();
        
        foreach ($predictions as $prediction) {
            $tips = collect($prediction->tips)->map(function ($tip) {
                return [
                    'option' => $tip['option'],
                    'selected' => $tip['status'] === 'selected'
                ];
            })->toArray();
            
            $prediction->tips = $tips;
            $prediction->save();
        }
    }
}; 