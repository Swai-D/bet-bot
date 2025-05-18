<?php

namespace App\Console\Commands;

use App\Models\Game;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScrapePredictions extends Command
{
    protected $signature = 'scrape:predictions';
    protected $description = 'Scrape predictions from Adibet';

    public function handle()
    {
        try {
            $response = Http::get('https://adibet.com/api/predictions');
            
            if (!$response->successful()) {
                throw new \Exception('Failed to fetch predictions');
            }

            $predictions = $response->json();

            foreach ($predictions as $prediction) {
                Game::create([
                    'home_team' => $prediction['home_team'],
                    'away_team' => $prediction['away_team'],
                    'match_date' => $prediction['match_date'],
                    'tips' => $prediction['tips'],
                    'country' => $prediction['country'],
                    'league' => $prediction['league'],
                    'status' => 'pending'
                ]);
            }

            $this->info('Successfully scraped predictions');
            Log::info('Successfully scraped predictions', ['count' => count($predictions)]);

        } catch (\Exception $e) {
            $this->error('Failed to scrape predictions: ' . $e->getMessage());
            Log::error('Failed to scrape predictions', ['error' => $e->getMessage()]);
        }
    }
} 