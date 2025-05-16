<?php

namespace App\Services;

use App\Models\Bet;
use Illuminate\Support\Facades\Log;

class BetpawaAutomation
{
    protected $betpawaBot;

    public function __construct()
    {
        $this->betpawaBot = new BetpawaBot();
    }

    public function placeBets(array $tips, int $stake)
    {
        try {
            $result = $this->betpawaBot->placeBet($tips, $stake);

            if ($result['success']) {
                $this->saveBet($tips, $result);
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error placing bets: ' . $e->getMessage());
            return false;
        }
    }

    protected function saveBet(array $tips, array $result)
    {
        $bet = new Bet();
        $bet->predictions = json_encode($tips);
        $bet->confirmation = $result['confirmation'] ?? null;
        $bet->status = 'pending';
        $bet->save();
    }

    public function checkBetStatus(Bet $bet)
    {
        try {
            $result = $this->betpawaBot->checkBetStatus($bet->id);

            if ($result['success']) {
                $bet->status = $result['status'];
                $bet->save();
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Error checking bet status: ' . $e->getMessage());
            return false;
        }
    }

    public function getAccountBalance()
    {
        try {
            return $this->betpawaBot->getAccountBalance();
        } catch (\Exception $e) {
            Log::error('Error getting account balance: ' . $e->getMessage());
            return 0;
        }
    }
} 