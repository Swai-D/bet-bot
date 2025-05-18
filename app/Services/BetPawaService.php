<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class BetPawaService
{
    private $baseUrl = 'https://m.betpawa.tz';
    private $sessionId;
    private $puppeteer;

    public function __construct()
    {
        $this->sessionId = config('services.betpawa.session_id');
    }

    /**
     * Initialize Puppeteer browser
     */
    private function initPuppeteer()
    {
        try {
            $this->puppeteer = new \Nesk\Puphpeteer\Puppeteer;
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to initialize Puppeteer: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Login to BetPawa using mobile web interface
     */
    public function login()
    {
        if (!$this->initPuppeteer()) {
            return false;
        }

        try {
            $browser = $this->puppeteer->launch([
                'headless' => false,
                'args' => ['--no-sandbox']
            ]);

            $page = $browser->newPage();
            
            // Set mobile viewport
            $page->setViewport(['width' => 375, 'height' => 667]);
            $page->setUserAgent('Mozilla/5.0 (Linux; Android 10; Mobile)');

            // Navigate to login page
            $page->goto($this->baseUrl . '/login', ['waitUntil' => 'networkidle2']);

            // Fill login form
            $page->type('#phoneNumberInput', config('services.betpawa.phone'));
            $page->type('#passwordInput', config('services.betpawa.password'));
            $page->click('#loginButton');

            // Wait for navigation
            $page->waitForNavigation(['waitUntil' => 'networkidle2']);

            // Get session cookie
            $cookies = $page->cookies();
            foreach ($cookies as $cookie) {
                if ($cookie['name'] === 'session_id') {
                    $this->sessionId = $cookie['value'];
                    Cache::put('betpawa_session_id', $this->sessionId, now()->addHours(24));
                    break;
                }
            }

            $browser->close();
            return true;

        } catch (\Exception $e) {
            Log::error('BetPawa login failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Place a bet on a match
     */
    public function placeBet($matchId, $selection, $stake)
    {
        try {
            $response = Http::withHeaders([
                'Cookie' => 'session_id=' . $this->sessionId
            ])->post($this->baseUrl . '/api/bets/place', [
                'match_id' => $matchId,
                'selection' => $selection,
                'stake' => $stake
            ]);

            if ($response->successful()) {
                Log::info('Bet placed successfully', [
                    'match_id' => $matchId,
                    'selection' => $selection,
                    'stake' => $stake
                ]);
                return true;
            }

            Log::error('Failed to place bet', [
                'response' => $response->json(),
                'match_id' => $matchId
            ]);
            return false;

        } catch (\Exception $e) {
            Log::error('Error placing bet: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get available matches
     */
    public function getMatches()
    {
        try {
            $response = Http::withHeaders([
                'Cookie' => 'session_id=' . $this->sessionId
            ])->get($this->baseUrl . '/api/matches/available');

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to fetch matches', [
                'response' => $response->json()
            ]);
            return [];

        } catch (\Exception $e) {
            Log::error('Error fetching matches: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Check if session is valid
     */
    public function isSessionValid()
    {
        try {
            $response = Http::withHeaders([
                'Cookie' => 'session_id=' . $this->sessionId
            ])->get($this->baseUrl . '/api/user/profile');

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
} 