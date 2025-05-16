<?php

namespace App\Services;

use App\Models\Bet;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;

class BetpawaAutomation
{
    protected $baseUrl = 'https://api.betpawa.co.tz/api/v1';
    protected $session;
    protected $cookies = [];
    protected $isLoggedIn = false;

    public function __construct()
    {
        $this->session = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->withOptions([
            'verify' => false, // Disable SSL verification for testing
        ]);
    }

    public function login($username = null, $password = null)
    {
        try {
            // Use credentials from parameters or .env file
            $username = $username ?? env('BETPAWA_USERNAME');
            $password = $password ?? env('BETPAWA_PASSWORD');

            if (!$username || !$password) {
                Log::error('Betpawa credentials not found');
                return false;
            }

            // Attempt login
            $loginResponse = $this->session->post($this->baseUrl . '/auth/login', [
                'phoneNumber' => $username,
                'password' => $password,
            ]);

            if ($loginResponse->successful()) {
                $data = $loginResponse->json();
                if ($data['success']) {
                    // Store token
                    $this->session = $this->session->withToken($data['token']);
                return true;
                }
            }

            Log::error('Login failed: ' . $loginResponse->body());
            return false;

        } catch (\Exception $e) {
            Log::error('Login error: ' . $e->getMessage());
            return false;
        }
    }

    public function searchMatch($matchName)
    {
        try {
            if (!$this->isLoggedIn) {
                throw new \Exception('Not logged in to Betpawa');
            }

            Log::info("Searching for match: {$matchName}");
            
            $response = $this->session->get($this->baseUrl . '/search', [
                'query' => [
                    'q' => $matchName
                ]
            ]);

            $data = json_decode($response->body(), true);
            
            if (!$data['success']) {
                Log::error('Search failed: ' . $data['message']);
                return [];
            }

            $matches = array_map(function($match) {
                return [
                    'id' => $match['id'],
                    'type' => $match['type'],
                    'odds' => $match['odds']
                ];
            }, $data['matches']);

            Log::info('Found ' . count($matches) . ' matches');
            return $matches;

        } catch (\Exception $e) {
            Log::error('Error searching match: ' . $e->getMessage());
            return [];
        }
    }

    public function placeBet($matchId, $oddType, $amount)
    {
        try {
            $response = $this->session->post($this->baseUrl . '/bets/place', [
                'matchId' => $matchId,
                'oddType' => $oddType,
                'amount' => $amount
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'bet_id' => $data['bet_id'] ?? null,
                    'message' => 'Bet placed successfully'
                ];
            }

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
            $response = $this->session->get($this->baseUrl . '/account/balance');

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'balance' => $data['balance'] ?? 0
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get balance'
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