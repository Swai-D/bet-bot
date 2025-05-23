<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Prediction;
use Carbon\Carbon;

class InsertTestMatchesCommand extends Command
{
    protected $signature = 'test:insert-matches';
    protected $description = 'Insert test matches for OddsPortal scraper testing';

    public function handle()
    {
        $this->info('╔════════════════════════════════════════════════════════════╗');
        $this->info('║                INSERTING TEST MATCHES                       ║');
        $this->info('╚════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $testMatches = [
            [
                'match' => 'Heidenheim vs Elversberg',
                'country' => 'Germany',
                'league' => 'Bundesliga',
                'date' => Carbon::now()->format('Y-m-d') . ' 15:30:00',
                'tips' => [
                    [
                        'option' => '+2.5',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ]
                ]
            ],
            [
                'match' => 'BKMA vs Alashkert',
                'country' => 'Armenia',
                'league' => 'Unknown League',
                'date' => Carbon::now()->format('Y-m-d') . ' 16:00:00',
                'tips' => [
                    [
                        'option' => '2',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ],
                    [
                        'option' => '+2.5',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ]
                ]
            ],
            [
                'match' => 'Panetolikos vs Panserraikos',
                'country' => 'Greece',
                'league' => 'Unknown League',
                'date' => Carbon::now()->format('Y-m-d') . ' 17:00:00',
                'tips' => [
                    [
                        'option' => '2',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ]
                ]
            ],
            [
                'match' => 'Manchester United vs Liverpool',
                'country' => 'England',
                'league' => 'Premier League',
                'date' => Carbon::now()->addDay()->format('Y-m-d') . ' 14:00:00',
                'tips' => [
                    [
                        'option' => '1',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ],
                    [
                        'option' => 'GG',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ]
                ]
            ],
            [
                'match' => 'Barcelona vs Real Madrid',
                'country' => 'Spain',
                'league' => 'La Liga',
                'date' => Carbon::now()->addDay()->format('Y-m-d') . ' 16:00:00',
                'tips' => [
                    [
                        'option' => '1X',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ],
                    [
                        'option' => '+2.5',
                        'odd' => 'N/A',
                        'status' => 'not selected'
                    ]
                ]
            ]
        ];

        foreach ($testMatches as $match) {
            try {
                $prediction = Prediction::create([
                    'match_id' => uniqid('test_'),
                    'match' => $match['match'],
                    'country' => $match['country'],
                    'league' => $match['league'],
                    'date' => Carbon::parse($match['date']),
                    'tips' => $match['tips'],
                    'score' => 0
                ]);

                $this->info("✅ Inserted: {$match['match']}");
            } catch (\Exception $e) {
                $this->error("❌ Failed to insert {$match['match']}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info('Test matches inserted successfully!');
    }
} 