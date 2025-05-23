<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class SportyTraderScraper
{
    protected $baseUrl = 'https://www.sportytrader.com/en/betting-tips/football/';
    protected $oddsUrl = 'https://www.sportytrader.com/en/odds/football/';

    protected $predictionMap = [
        'wins the match' => '1',
        'to win' => '1',
        'draw no bet' => 'DNB',
        'Both teams to score' => 'GG',
        'Both teams to score (NO)' => 'NG',
        'Over 2.5 goals' => '+2.5',
        'Over 2.5' => '+2.5',
        'Under 2.5' => '-2.5',
        'to win without conceding' => '1 & NG',
        'to win and both teams to score' => '1 & GG',
        'to Win and Both Teams to Score - Yes' => '1 & GG',
        'to Win and Over 2.5 Goals' => '1 & +2.5',
        'Both Teams to Score and Over 2.5 Goals' => 'GG & +2.5',
        'Over 2.5 goals and both teams to score' => '+2.5 & GG'
    ];

    public function getPredictionsWithOdds()
    {
        try {
            // Create a temporary Node.js script
            $scriptPath = storage_path('app/temp_scraper.js');
            $this->createScraperScript($scriptPath);

            // Run the script with a longer timeout
            $process = new Process(['node', $scriptPath]);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            if (!$process->isSuccessful()) {
                Log::error('Scraper process failed: ' . $process->getErrorOutput());
                throw new \Exception('Failed to run scraper: ' . $process->getErrorOutput());
            }

            $output = $process->getOutput();
            Log::info('Raw scraper output: ' . $output);

            // Clean up any non-JSON output
            $jsonStart = strpos($output, '{');
            $jsonEnd = strrpos($output, '}') + 1;
            
            if ($jsonStart === false || $jsonEnd === false) {
                throw new \Exception('No JSON data found in output');
            }

            $jsonOutput = substr($output, $jsonStart, $jsonEnd - $jsonStart);
            Log::info('Cleaned JSON output: ' . $jsonOutput);

            $result = json_decode($jsonOutput, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('JSON decode error: ' . json_last_error_msg());
                throw new \Exception('Invalid JSON output: ' . json_last_error_msg());
            }
            
            if (!$result || !isset($result['success'])) {
                Log::error('Invalid result structure: ' . print_r($result, true));
                throw new \Exception('Invalid scraper output structure');
            }

            // Process predictions to standardize formats
            if (isset($result['data'])) {
                $result['data'] = array_map(function($prediction) {
                    if (isset($prediction['tips']) && is_array($prediction['tips'])) {
                        $prediction['tips'] = array_map(function($tip) {
                            // Standardize prediction format
                            $prediction = $tip['prediction'];
                            foreach ($this->predictionMap as $key => $value) {
                                if (stripos($prediction, $key) !== false) {
                                    $prediction = $value;
                                    break;
                                }
                            }
                            
                            // Calculate confidence based on odds
                            $odds = floatval($tip['odds']);
                            $confidence = 'Medium';
                            if ($odds >= 2.5) {
                                $confidence = 'High';
                            } elseif ($odds <= 1.5) {
                                $confidence = 'Low';
                            }

                            return [
                                'prediction' => $prediction,
                                'odds' => $tip['odds'],
                                'confidence' => $confidence
                            ];
                        }, $prediction['tips']);
                    }
                    return $prediction;
                }, $result['data']);
            }

            // Clean up
            unlink($scriptPath);

            return $result;

        } catch (\Exception $e) {
            Log::error('SportyTrader scraper error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'timestamp' => now()->toIso8601String()
            ];
        }
    }

    protected function createScraperScript($path)
    {
        $script = <<<'EOT'
import { chromium } from 'playwright';

async function scrapePredictions() {
    const browser = await chromium.launch({ 
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    try {
        const page = await browser.newPage();
        await page.setViewportSize({ width: 1280, height: 800 });

        // Set a timeout for all operations
        page.setDefaultTimeout(60000); // 1 minute timeout

        // Navigate to predictions page
        console.log('Navigating to predictions page...');
        await page.goto('https://www.sportytrader.com/en/betting-tips/football/', {
            waitUntil: 'domcontentloaded',
            timeout: 60000
        });

        // Wait for the page to load
        console.log('Waiting for page to load...');
        await page.waitForLoadState('domcontentloaded');

        // Extract predictions
        console.log('Extracting predictions...');
        const predictions = await page.evaluate(() => {
            // Find all prediction containers
            const predictionContainers = document.querySelectorAll('div.grid.grid-cols-7');
            console.log(`Found ${predictionContainers.length} prediction containers`);

            return Array.from(predictionContainers).map(container => {
                try {
                    // Debug: Log container HTML
                    console.log('Processing container:', container.outerHTML);

                    // Extract date and time
                    const dateTimeElement = container.querySelector('p.dark\\:text-white.font-bold');
                    const dateTime = dateTimeElement ? dateTimeElement.textContent.trim() : '';
                    const [date, time] = dateTime.split(',').map(s => s.trim());

                    // Extract league and country
                    const leagueElement = container.querySelector('p.dark\\:text-white.text-sm');
                    const leagueText = leagueElement ? leagueElement.textContent.trim() : '';
                    const [country, league] = leagueText.split('-').map(s => s.trim());

                    // Find the match details container
                    const matchContainer = container.nextElementSibling;
                    if (!matchContainer) {
                        console.log('No match container found');
                        return null;
                    }

                    // Extract teams
                    const homeTeamElement = matchContainer.querySelector('div.col-span-5 span.font-semibold');
                    const awayTeamElement = matchContainer.querySelector('div.col-span-5:last-child span.font-semibold');
                    const homeTeam = homeTeamElement ? homeTeamElement.textContent.trim() : '';
                    const awayTeam = awayTeamElement ? awayTeamElement.textContent.trim() : '';

                    // Find the prediction container
                    const predictionContainer = matchContainer.nextElementSibling;
                    if (!predictionContainer) {
                        console.log('No prediction container found');
                        return null;
                    }

                    // Extract prediction and odds
                    const predictionElement = predictionContainer.querySelector('p.font-semibold');
                    const oddsElement = predictionContainer.querySelector('div.col-span-3 span.font-bold');
                    const prediction = predictionElement ? predictionElement.textContent.trim() : '';
                    const odds = oddsElement ? oddsElement.textContent.trim() : '';

                    const result = {
                        match: `${homeTeam} vs ${awayTeam}`,
                        home_team: homeTeam,
                        away_team: awayTeam,
                        country: country || 'Unknown',
                        date: date || '',
                        time: time || '',
                        league: league || 'Unknown League',
                        tips: [{
                            prediction: prediction,
                            odds: odds
                        }]
                    };

                    console.log('Extracted prediction:', result);
                    return result;
                } catch (error) {
                    console.error('Error processing container:', error);
                    return null;
                }
            }).filter(Boolean);
        });

        console.log(`Successfully extracted ${predictions.length} predictions`);

        await browser.close();

        const result = {
            success: true,
            data: predictions,
            timestamp: new Date().toISOString()
        };

        // Ensure we only output the JSON
        process.stdout.write(JSON.stringify(result));

    } catch (error) {
        const errorResult = {
            success: false,
            error: error.message,
            timestamp: new Date().toISOString()
        };
        process.stdout.write(JSON.stringify(errorResult));
    } finally {
        await browser.close();
    }
}

scrapePredictions();
EOT;

        file_put_contents($path, $script);
    }
} 