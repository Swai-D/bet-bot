<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BetpawaBot
{
    protected $username;
    protected $password;
    protected $scriptPath;

    public function __construct()
    {
        $this->username = config('services.betpawa.username');
        $this->password = config('services.betpawa.password');
        $this->scriptPath = base_path('betpawa-bot.js');
    }

    public function placeBet(array $tips, int $stake)
    {
        try {
            // Prepare tips data for the Node.js script
            $tipsData = json_encode([
                'username' => $this->username,
                'password' => $this->password,
                'tips' => $tips,
                'stake' => $stake
            ]);

            // Run the Node.js script
            $process = new Process(['node', $this->scriptPath, $tipsData]);
            $process->setTimeout(300); // 5 minutes timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception('Bet placement failed: ' . $process->getErrorOutput());
            }

            $result = json_decode($process->getOutput(), true);
            
            if (!$result['success']) {
                throw new \Exception('Bet placement failed: ' . ($result['error'] ?? 'Unknown error'));
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('Error placing bet: ' . $e->getMessage());
            throw $e;
        }
    }

    public function checkBetStatus($betId)
    {
        try {
            $process = new Process(['node', $this->scriptPath, '--check-status', $betId]);
            $process->setTimeout(60); // 1 minute timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception('Status check failed: ' . $process->getErrorOutput());
            }

            return json_decode($process->getOutput(), true);

        } catch (\Exception $e) {
            Log::error('Error checking bet status: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getAccountBalance()
    {
        try {
            $process = new Process(['node', $this->scriptPath, '--check-balance']);
            $process->setTimeout(60); // 1 minute timeout
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \Exception('Balance check failed: ' . $process->getErrorOutput());
            }

            $result = json_decode($process->getOutput(), true);
            return $result['balance'] ?? 0;

        } catch (\Exception $e) {
            Log::error('Error getting account balance: ' . $e->getMessage());
            throw $e;
        }
    }
} 