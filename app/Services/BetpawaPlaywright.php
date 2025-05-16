<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BetpawaPlaywright
{
    protected $browser;
    protected $page;
    protected $screenshotPath;

    public function __construct()
    {
        $this->screenshotPath = storage_path('app/betpawa-screenshots');
        if (!file_exists($this->screenshotPath)) {
            mkdir($this->screenshotPath, 0777, true);
        }
    }

    public function login($username, $password)
    {
        try {
            Log::info('Attempting to login to Betpawa...');
            
            // Create a new browser instance
            $browser = new Browsershot();
            
            // Set viewport and user agent
            $browser->setScreenshotType('jpeg', 100)
                   ->windowSize(1920, 1080)
                   ->userAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

            // Navigate to login page
            $browser->setUrl('https://www.betpawa.co.tz/login');
            
            // Take screenshot before login
            $browser->save($this->screenshotPath . '/before-login.jpg');
            
            // Fill login form
            $browser->evaluate("
                document.querySelector('#login-form-phoneNumber').value = '{$username}';
                document.querySelector('#login-form-password-input').value = '{$password}';
                document.querySelector('input[data-test-id=\"logInButton\"]').click();
            ");
            
            // Wait for login to complete
            sleep(5);
            
            // Take screenshot after login
            $browser->save($this->screenshotPath . '/after-login.jpg');
            
            // Check if login was successful
            $isLoggedIn = $browser->evaluate("
                document.querySelector('.button.balance') !== null
            ");
            
            if ($isLoggedIn) {
                Log::info('Login successful');
                return true;
            }
            
            Log::error('Login failed - Balance button not found');
            return false;

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return false;
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
            $browser = new Browsershot();
            $browser->setUrl('https://www.betpawa.co.tz/account');
            
            // Get balance
            $balance = $browser->evaluate("
                document.querySelector('.balance-amount').textContent
            ");
            
            return [
                'success' => true,
                'balance' => floatval(str_replace(['TZS', ','], '', $balance))
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