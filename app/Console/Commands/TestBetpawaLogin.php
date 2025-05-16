<?php

namespace App\Console\Commands;

use App\Services\BetpawaPlaywright;
use Illuminate\Console\Command;

class TestBetpawaLogin extends Command
{
    protected $signature = 'betpawa:test-login';
    protected $description = 'Test Betpawa login using browser automation';

    public function handle()
    {
        $this->info('Testing Betpawa login...');

        $betpawa = new BetpawaPlaywright();
        
        // Get credentials from .env file
        $username = env('BETPAWA_USERNAME');
        $password = env('BETPAWA_PASSWORD');

        if (!$username || !$password) {
            $this->error('Please set BETPAWA_USERNAME and BETPAWA_PASSWORD in your .env file');
            return;
        }

        $result = $betpawa->login($username, $password);

        if ($result) {
            $this->info('Login successful!');
            
            // Get balance
            $balance = $betpawa->getBalance();
            if ($balance['success']) {
                $this->info('Current balance: ' . $balance['balance']);
            } else {
                $this->error('Failed to get balance: ' . $balance['message']);
            }
        } else {
            $this->error('Login failed!');
            $this->info('Check screenshots in: ' . storage_path('app/betpawa-screenshots'));
        }
    }
} 