<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prediction;
use Carbon\Carbon;

class InsertBetisValenciaPredictionCommand extends Command
{
    protected $signature = 'test:insert-betis-valencia';
    protected $description = 'Insert Betis vs Valencia prediction for OddsPortal scraper testing';

    public function handle()
    {
        $date = Carbon::now()->format('Y-m-d') . ' 22:00:00'; // adjust time if needed

        $prediction = Prediction::updateOrCreate(
            [
                'match' => 'Betis vs Valencia',
                'date' => $date,
            ],
            [
                'match_id' => uniqid('betis_valencia_'),
                'country' => 'Spain',
                'league' => 'La Liga',
                'tips' => [
                    [
                        'option' => 'GG',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ]
                ],
                'score' => 0
            ]
        );

        $this->info('✅ Prediction for Betis vs Valencia (GG) inserted!');
    }
}
