<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class BetpawaBotCommand extends Command
{
    protected $signature = 'betpawa:bot {action : The action to perform (balance|login|help)}';
    protected $description = 'Run Betpawa bot commands';

    public function handle()
    {
        try {
            $action = $this->argument('action');
            $nodePath = env('NODE_PATH', 'node');
            $botPath = base_path('betpawa-bot.js');
            $loginPath = base_path('betpawa-login.js');

            // Validate environment
            $this->validateEnvironment();

            switch ($action) {
                case 'balance':
                    $this->info('Checking Betpawa balance...');
                    $process = new Process([$nodePath, $botPath, 'balance']);
                    break;

                case 'login':
                    $this->info('Logging into Betpawa...');
                    $process = new Process([$nodePath, $loginPath, 'login']);
                    break;

                case 'help':
                    $this->info('Showing help...');
                    $process = new Process([$nodePath, $botPath, 'help']);
                    break;

                default:
                    $this->error("Unknown action: {$action}");
                    $this->info('Available actions: balance, login, help');
                    return 1;
            }

            // Set process timeout from env or default to 5 minutes
            $process->setTimeout(env('BOT_TIMEOUT', 300));
            
            // Run the process with real-time output
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    $this->error($buffer);
                    Log::error("Betpawa Bot Error: {$buffer}");
                } else {
                    $this->line($buffer);
                    Log::info("Betpawa Bot Output: {$buffer}");
                }
            });

            // Check if process was successful
            if (!$process->isSuccessful()) {
                $this->error('Command failed with exit code: ' . $process->getExitCode());
                Log::error('Betpawa Bot Command Failed', [
                    'action' => $action,
                    'exit_code' => $process->getExitCode(),
                    'output' => $process->getOutput(),
                    'error' => $process->getErrorOutput()
                ]);
                return 1;
            }

            $this->info('Command completed successfully');
            return 0;

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('Betpawa Bot Exception', [
                'action' => $action ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    protected function validateEnvironment()
    {
        $requiredEnv = ['BETPAWA_PHONE', 'BETPAWA_PASSWORD'];
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