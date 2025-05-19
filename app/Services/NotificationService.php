<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class NotificationService
{
    private $telegramBotToken;
    private $telegramChatId;

    public function __construct()
    {
        $this->telegramBotToken = config('services.telegram.bot_token');
        $this->telegramChatId = config('services.telegram.chat_id');
    }

    /**
     * Send notification about bet placement
     */
    public function notifyBetPlaced($match, $tip, $odds, $stake)
    {
        if (!Cache::get('enable_notifications', false)) {
            return;
        }

        $message = "🎯 *New Bet Placed*\n\n";
        $message .= "Match: {$match}\n";
        $message .= "Tip: {$tip}\n";
        $message .= "Odds: {$odds}\n";
        $message .= "Stake: {$stake} TZS\n";
        $message .= "Time: " . now()->format('H:i:s');

        $this->sendTelegramMessage($message);
    }

    /**
     * Send notification about bet result
     */
    public function notifyBetResult($match, $tip, $result, $winLoss)
    {
        if (!Cache::get('enable_notifications', false)) {
            return;
        }

        $emoji = $result === 'W' ? '✅' : ($result === 'L' ? '❌' : '⚪');
        $message = "{$emoji} *Bet Result*\n\n";
        $message .= "Match: {$match}\n";
        $message .= "Tip: {$tip}\n";
        $message .= "Result: {$result}\n";
        $message .= "Win/Loss: {$winLoss} TZS";

        $this->sendTelegramMessage($message);
    }

    /**
     * Send notification about scraper status
     */
    public function notifyScraperStatus($status, $matchesCount = 0)
    {
        if (!Cache::get('enable_notifications', false)) {
            return;
        }

        $message = "🔄 *Scraper Status*\n\n";
        $message .= "Status: {$status}\n";
        if ($matchesCount > 0) {
            $message .= "Matches Found: {$matchesCount}";
        }

        $this->sendTelegramMessage($message);
    }

    /**
     * Send notification about bot status
     */
    public function notifyBotStatus($status)
    {
        if (!Cache::get('enable_notifications', false)) {
            return;
        }

        $emoji = $status ? '🟢' : '🔴';
        $message = "{$emoji} *Bot Status*\n\n";
        $message .= "Status: " . ($status ? 'Running' : 'Stopped');

        $this->sendTelegramMessage($message);
    }

    /**
     * Send message via Telegram
     */
    private function sendTelegramMessage($message)
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->telegramBotToken}/sendMessage", [
                'chat_id' => $this->telegramChatId,
                'text' => $message,
                'parse_mode' => 'Markdown'
            ]);

            if (!$response->successful()) {
                Log::error('Failed to send Telegram notification: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Error sending Telegram notification: ' . $e->getMessage());
        }
    }
} 