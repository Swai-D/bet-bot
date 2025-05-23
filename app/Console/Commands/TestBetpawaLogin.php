<?php

namespace App\Console\Commands;

use App\Services\BetpawaPlaywright;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestBetpawaLogin extends Command
{
    protected $signature = 'betpawa:test-login';
    protected $description = 'Test Betpawa login using browser automation';

    public function handle()
    {
        try {
            $this->info('Testing Betpawa login...');

            // Validate environment
            $this->validateEnvironment();

            $betpawa = new BetpawaPlaywright();
            
            // Get credentials from .env file
            $username = env('BETPAWA_USERNAME');
            $password = env('BETPAWA_PASSWORD');

            $this->info('Attempting login...');
            $result = $betpawa->login($username, $password);

            if ($result['success']) {
                $this->info($result['message']);
                Log::info('Betpawa Login Test Success', $result);
                
                if (isset($result['balance'])) {
                    $this->info('Current balance: ' . $result['balance']);
                }
            } else {
                $this->error('Login failed: ' . $result['message']);
                Log::error('Betpawa Login Test Failed', $result);
            }

            $this->info('Screenshots saved in: ' . public_path('screenshots'));
            $this->info('You can view them at: http://localhost:8000/screenshots/');

            return $result['success'] ? 0 : 1;

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Betpawa Login Test Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function validateEnvironment()
    {
        $requiredEnv = ['BETPAWA_USERNAME', 'BETPAWA_PASSWORD'];
        $missing = [];

        foreach ($requiredEnv as $env) {
            if (!env($env)) {
                $missing[] = $env;
            }
        }

        if (!empty($missing)) {
            $this->error('Missing required environment variables: ' . implode(', ', $missing));
            $this->info('Please set these variables in your .env file');
            exit(1);
        }
    }
} 