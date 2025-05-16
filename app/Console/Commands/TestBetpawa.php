<?php

namespace App\Console\Commands;

use App\Services\BetpawaAutomation;
use Illuminate\Console\Command;

class TestBetpawa extends Command
{
    protected $signature = 'betpawa:test';
    protected $description = 'Test Betpawa automation';

    public function handle()
    {
        $this->info('Testing Betpawa automation...');
        
        $betpawa = new BetpawaAutomation();
        
        // Get credentials from .env
        $username = env('BETPAWA_USERNAME');
        $password = env('BETPAWA_PASSWORD');
        
        if (!$username || !$password) {
            $this->error('Please set BETPAWA_USERNAME and BETPAWA_PASSWORD in .env file');
            return Command::FAILURE;
        }
        
        // Try to login
        $this->info('Attempting to login...');
        $loginResult = $betpawa->login($username, $password);
        
        if (!$loginResult) {
            $this->error('Failed to login to Betpawa');
            return Command::FAILURE;
        }
        
        $this->info('Successfully logged in!');
        
        // Get balance
        $this->info('Getting account balance...');
        $balance = $betpawa->getBalance();
        
        if ($balance['success']) {
            $this->info('Current balance: ' . $balance['balance']);
        } else {
            $this->error('Failed to get balance: ' . $balance['message']);
        }
        
        return Command::SUCCESS;
    }
} 