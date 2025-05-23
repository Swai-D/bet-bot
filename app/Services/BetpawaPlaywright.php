<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class BetpawaPlaywright
{
    protected $screenshotPath;

    public function __construct()
    {
        // Create screenshots directory in public folder for easy access
        $this->screenshotPath = str_replace('\\', '/', public_path('screenshots'));
        
        // Log the screenshot path
        Log::info('Screenshot path: ' . $this->screenshotPath);
        
        if (!file_exists($this->screenshotPath)) {
            Log::info('Creating screenshots directory...');
            if (!mkdir($this->screenshotPath, 0777, true)) {
                Log::error('Failed to create screenshots directory');
                throw new \Exception('Failed to create screenshots directory');
            }
            Log::info('Screenshots directory created successfully');
        }
        
        // Test if directory is writable
        if (!is_writable($this->screenshotPath)) {
            Log::error('Screenshots directory is not writable');
            throw new \Exception('Screenshots directory is not writable');
        }
        
        // Create a test file to verify write permissions
        $testFile = $this->screenshotPath . '/test.txt';
        if (file_put_contents($testFile, 'test') === false) {
            Log::error('Failed to write test file to screenshots directory');
            throw new \Exception('Failed to write test file to screenshots directory');
        }
        unlink($testFile);
        Log::info('Screenshots directory is writable');
    }

    public function login($username, $password)
    {
        try {
            Log::info('Attempting to login to Betpawa...');
            
            // Create a temporary JavaScript file for login
            $scriptPath = storage_path('app/temp-login.js');
            $script = <<<JS
            import { chromium } from 'playwright';
            import fs from 'fs';
            import path from 'path';
            
            async function login() {
                console.log('Starting login process...');
                const browser = await chromium.launch({ 
                    headless: false,
                    slowMo: 100, // Add delay between actions
                    args: ['--no-sandbox', '--disable-setuid-sandbox']
                });
                try {
                    console.log('Browser launched successfully');
                    const context = await browser.newContext();
                    const page = await context.newPage();
                    
                    console.log('Navigating to Betpawa...');
                    // Navigate to Betpawa
                    await page.goto('https://www.betpawa.co.tz', {
                        waitUntil: 'networkidle',
                        timeout: 30000
                    });
                    console.log('Navigation complete');
                    
                    // Take screenshot of homepage
                    const screenshotPath = path.resolve('{$this->screenshotPath}/1-homepage.png');
                    console.log('Taking homepage screenshot...');
                    await page.screenshot({ path: screenshotPath });
                    console.log('Screenshot saved to: ' + screenshotPath);
                    
                    // First check if already logged in
                    console.log('Checking if already logged in...');
                    const isAlreadyLoggedIn = await page.waitForSelector('.button.balance', { timeout: 5000 })
                        .then(() => true)
                        .catch(() => false);
                    
                    if (isAlreadyLoggedIn) {
                        console.log('Already logged in, getting balance...');
                        const balance = await page.textContent('.button.balance');
                        const screenshotPath = path.resolve('{$this->screenshotPath}/2-already-logged-in.png');
                        await page.screenshot({ path: screenshotPath });
                        console.log('Screenshot saved to: ' + screenshotPath);
                        console.log(JSON.stringify({ 
                            success: true, 
                            message: 'Already logged in',
                            balance
                        }));
                        
                        // Wait for 5 seconds before closing
                        await new Promise(resolve => setTimeout(resolve, 5000));
                        return;
                    }
                    
                    console.log('Not logged in, proceeding with login...');
                    // If not logged in, proceed with login
                    await page.click('a[href="/login"]');
                    await page.waitForLoadState('networkidle');
                    const screenshotPath = path.resolve('{$this->screenshotPath}/3-login-page.png');
                    await page.screenshot({ path: screenshotPath });
                    console.log('Screenshot saved to: ' + screenshotPath);
                    
                    // Fill login form
                    console.log('Filling login form...');
                    await page.fill('#login-form-phoneNumber', '{$username}');
                    await page.fill('#login-form-password-input', '{$password}');
                    const formScreenshotPath = path.resolve('{$this->screenshotPath}/4-filled-form.png');
                    await page.screenshot({ path: formScreenshotPath });
                    console.log('Screenshot saved to: ' + formScreenshotPath);
                    
                    // Click login submit button
                    console.log('Clicking login button...');
                    await page.click('input[data-test-id="logInButton"]');
                    await page.waitForLoadState('networkidle');
                    
                    // Check if login was successful
                    console.log('Checking login result...');
                    const isLoggedIn = await page.waitForSelector('.button.balance', { timeout: 5000 })
                        .then(() => true)
                        .catch(() => false);
                    
                    if (isLoggedIn) {
                        console.log('Login successful, getting balance...');
                        const balance = await page.textContent('.button.balance');
                        const screenshotPath = path.resolve('{$this->screenshotPath}/5-login-success.png');
                        await page.screenshot({ path: screenshotPath });
                        console.log('Screenshot saved to: ' + screenshotPath);
                        console.log(JSON.stringify({ 
                            success: true, 
                            message: 'Login successful',
                            balance 
                        }));
                    } else {
                        console.log('Login failed...');
                        const screenshotPath = path.resolve('{$this->screenshotPath}/5-login-failed.png');
                        await page.screenshot({ path: screenshotPath });
                        console.log('Screenshot saved to: ' + screenshotPath);
                        console.log(JSON.stringify({ 
                            success: false, 
                            message: 'Login failed' 
                        }));
                    }
                    
                    // Wait for 5 seconds before closing
                    console.log('Waiting 5 seconds before closing...');
                    await new Promise(resolve => setTimeout(resolve, 5000));
                    
                } catch (error) {
                    console.error('Error occurred:', error);
                    const screenshotPath = path.resolve('{$this->screenshotPath}/error.png');
                    await page.screenshot({ path: screenshotPath });
                    console.log('Error screenshot saved to: ' + screenshotPath);
                    console.log(JSON.stringify({ 
                        success: false, 
                        message: error.message 
                    }));
                } finally {
                    console.log('Closing browser...');
                    await browser.close();
                }
            }
            
            login().catch(error => {
                console.error('Fatal error:', error);
                process.exit(1);
            });
            JS;
            
            file_put_contents($scriptPath, $script);
            
            // Run the script
            $process = new Process(['node', $scriptPath]);
            $process->setTimeout(60); // Set timeout to 60 seconds
            $process->run();
            
            // Get the output
            $output = $process->getOutput();
            Log::info('Script output: ' . $output);
            
            if (!$process->isSuccessful()) {
                Log::error('Script failed: ' . $process->getErrorOutput());
                throw new \Exception('Script failed: ' . $process->getErrorOutput());
            }
            
            $result = json_decode($output, true);
            
            // Clean up
            unlink($scriptPath);
            
            if ($result && $result['success']) {
                Log::info($result['message']);
                return [
                    'success' => true,
                    'message' => $result['message'],
                    'balance' => $result['balance'] ?? null
                ];
            }
            
            Log::error('Login failed: ' . ($result['message'] ?? 'Unknown error'));
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Unknown error'
            ];

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function searchMatch($matchName)
    {
        try {
            Log::info("Searching for match: {$matchName}");
            
            $browser = new Browsershot();
            $browser->setUrl('https://www.betpawa.co.tz/search');
            
            // Fill search form
            $browser->evaluate("
                document.querySelector('input[type=\"search\"]').value = '{$matchName}';
                document.querySelector('input[type=\"search\"]').dispatchEvent(new Event('input'));
            ");
            
            // Wait for results
            sleep(3);
            
            // Get search results
            $results = $browser->evaluate("
                Array.from(document.querySelectorAll('.match-row')).map(row => ({
                    id: row.dataset.matchId,
                    name: row.querySelector('.match-name').textContent,
                    odds: row.querySelector('.odds').textContent
                }))
            ");
            
            Log::info('Found ' . count($results) . ' matches');
            return $results;

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage());
            return [];
        }
    }

    public function placeBet($matchId, $oddType, $amount)
    {
        try {
            Log::info("Placing bet for match {$matchId}");
            
            $browser = new Browsershot();
            $browser->setUrl("https://www.betpawa.co.tz/match/{$matchId}");
            
            // Select odd type
            $browser->evaluate("
                document.querySelector(`[data-odd-type=\"${oddType}\"]`).click();
            ");
            
            // Enter amount
            $browser->evaluate("
                document.querySelector('input[type=\"number\"]').value = '{$amount}';
                document.querySelector('input[type=\"number\"]').dispatchEvent(new Event('input'));
            ");
            
            // Place bet
            $browser->evaluate("
                document.querySelector('button[data-test-id=\"placeBetButton\"]').click();
            ");
            
            // Wait for confirmation
            sleep(3);
            
            // Check if bet was placed successfully
            $isSuccess = $browser->evaluate("
                document.querySelector('.bet-success-message') !== null
            ");
            
            if ($isSuccess) {
                Log::info('Bet placed successfully');
                return [
                    'success' => true,
                    'message' => 'Bet placed successfully'
                ];
            }
            
            Log::error('Failed to place bet');
            return [
                'success' => false,
                'message' => 'Failed to place bet'
            ];

        } catch (\Exception $e) {
            Log::error('Place bet error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function getBalance()
    {
        try {
            // Create a temporary JavaScript file for getting balance
            $scriptPath = storage_path('app/temp-balance.js');
            $script = <<<JS
            import { chromium } from 'playwright';
            
            async function getBalance() {
                const browser = await chromium.launch({ 
                    headless: false,
                    slowMo: 100 // Add delay between actions
                });
                try {
                    const context = await browser.newContext();
                    const page = await context.newPage();
                    
                    // Navigate to Betpawa
                    await page.goto('https://www.betpawa.co.tz');
                    await page.waitForLoadState('networkidle');
                    
                    // Take screenshot of homepage
                    await page.screenshot({ path: '{$this->screenshotPath}/1-homepage.png' });
                    
                    // Check if already logged in
                    const isLoggedIn = await page.waitForSelector('.button.balance', { timeout: 5000 })
                        .then(() => true)
                        .catch(() => false);
                    
                    if (isLoggedIn) {
                        const balance = await page.textContent('.button.balance');
                        await page.screenshot({ path: '{$this->screenshotPath}/2-balance.png' });
                        console.log(JSON.stringify({ 
                            success: true, 
                            balance 
                        }));
                    } else {
                        await page.screenshot({ path: '{$this->screenshotPath}/2-not-logged-in.png' });
                        console.log(JSON.stringify({ 
                            success: false, 
                            message: 'Not logged in' 
                        }));
                    }
                    
                    // Wait for 5 seconds before closing
                    await new Promise(resolve => setTimeout(resolve, 5000));
                    
                } catch (error) {
                    await page.screenshot({ path: '{$this->screenshotPath}/error.png' });
                    console.log(JSON.stringify({ 
                        success: false, 
                        message: error.message 
                    }));
                } finally {
                    await browser.close();
                }
            }
            
            getBalance();
            JS;
            
            file_put_contents($scriptPath, $script);
            
            // Run the script
            $process = new Process(['node', $scriptPath]);
            $process->run();
            
            // Get the output
            $output = $process->getOutput();
            $result = json_decode($output, true);
            
            // Clean up
            unlink($scriptPath);
            
            if ($result && $result['success']) {
                return [
                    'success' => true,
                    'balance' => floatval(str_replace(['TZS', ','], '', $result['balance']))
                ];
            }
            
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Failed to get balance'
            ];

        } catch (\Exception $e) {
            Log::error('Get balance error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
} 