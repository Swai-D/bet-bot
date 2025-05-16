<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BettingService
{
    protected $scraperPath;
    protected $botPath;
    protected $storagePath;

    public function __construct()
    {
        // Use proper path escaping for Windows paths with spaces
        $this->scraperPath = escapeshellarg(base_path('adibet-scraper.js'));
        $this->botPath = escapeshellarg(base_path('betpawa-bot.js'));
        $this->storagePath = storage_path('app/betting');
    }

    public function scrapeMatches()
    {
        try {
            $output = [];
            $command = "node --experimental-modules {$this->scraperPath}";
            
            // Use proc_open for better process handling
            $process = proc_open($command, [
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w']  // stderr
            ], $pipes);

            if (!is_resource($process)) {
                throw new \Exception('Failed to start scraping process');
            }

            // Read output
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            // Close pipes
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Get return code
            $returnCode = proc_close($process);

            if ($returnCode !== 0) {
                throw new \Exception('Scraping process failed: ' . $stderr);
            }

            // Log the raw output for debugging
            Log::debug('Scraper output:', [
                'stdout' => $stdout,
                'stderr' => $stderr,
                'command' => $command,
                'returnCode' => $returnCode
            ]);

            // Try to find JSON in the output
            if (preg_match('/\{[\s\S]*\}/', $stdout, $matches)) {
                $jsonStr = $matches[0];
                Log::debug('Found JSON string:', ['json' => $jsonStr]);
            } else {
                Log::error('No JSON found in output', ['stdout' => $stdout]);
                throw new \Exception('No JSON data found in scraper output. Raw output: ' . substr($stdout, 0, 1000));
            }

            $matches = json_decode($jsonStr, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Failed to parse matches data: ' . json_last_error_msg() . "\nRaw data: " . substr($jsonStr, 0, 1000));
            }

            if (empty($matches)) {
                throw new \Exception('No matches found in the parsed data');
            }

            return $matches;
        } catch (\Exception $e) {
            Log::error('Scraping failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function selectMatches(array $matches, float $minTotalOdds = 3.00)
    {
        $today = Carbon::now()->format('d-m-Y');
        Log::debug('Looking for matches for date:', ['date' => $today, 'available_dates' => array_keys($matches)]);
        
        if (!isset($matches[$today])) {
            throw new \Exception('No matches found for today (' . $today . ')');
        }

        $todayMatches = $matches[$today];
        $selectedMatches = [];
        $totalOdds = 1.0;

        // Randomly select matches until we reach minimum odds
        while ($totalOdds < $minTotalOdds && count($todayMatches) > 0) {
            $randomIndex = array_rand($todayMatches);
            $match = $todayMatches[$randomIndex];
            
            if ($match['odd'] > 0) {  // Only select matches with valid odds
                $selectedMatches[] = [
                    'match' => $match['match'],
                    'option' => $match['prediction'],
                    'odd' => $match['odd']
                ];
                $totalOdds *= $match['odd'];
            }

            // Remove selected match to avoid duplicates
            unset($todayMatches[$randomIndex]);
        }

        if (count($selectedMatches) < 3) {
            throw new \Exception('Could not find enough matches with required odds');
        }

        return [
            'stake' => 1000, // Default stake, can be made configurable
            'tips' => $selectedMatches
        ];
    }

    public function placeBet(array $betData)
    {
        try {
            $jsonData = json_encode($betData);
            $command = "node {$this->botPath} " . escapeshellarg($jsonData);
            
            // Use proc_open for better process handling
            $process = proc_open($command, [
                1 => ['pipe', 'w'], // stdout
                2 => ['pipe', 'w']  // stderr
            ], $pipes);

            if (!is_resource($process)) {
                throw new \Exception('Failed to start betting process');
            }

            // Read output
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);

            // Close pipes
            fclose($pipes[1]);
            fclose($pipes[2]);

            // Get return code
            $returnCode = proc_close($process);

            if ($returnCode !== 0) {
                throw new \Exception('Bet placement failed: ' . $stderr);
            }

            return [
                'success' => true,
                'message' => 'Bet placed successfully',
                'details' => $stdout
            ];
        } catch (\Exception $e) {
            Log::error('Bet placement failed: ' . $e->getMessage());
            throw $e;
        }
    }
} 