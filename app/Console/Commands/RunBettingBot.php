<?php

namespace App\Console\Commands;

use App\Services\BetService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Barryvdh\DomPDF\Facade\Pdf;

class RunBettingBot extends Command
{
    protected $signature = 'betting:run 
        {--dry-run : Check status without making changes}
        {--force : Force check status}
        {--bet-id= : Check specific bet ID}
        {--date= : Filter bets by date (Y-m-d)}
        {--status= : Filter bets by status (pending/won/lost)}
        {--export= : Export statistics to file (json/csv/xlsx/pdf)}
        {--format=table : Output format (table/json/csv)}
        {--sort=date : Sort statistics by (date/amount/profit/streak)}';

    protected $description = 'Run the betting bot';

    protected $betService;
    protected $supportedFormats = ['table', 'json', 'csv', 'xlsx', 'pdf'];
    protected $supportedSorts = ['date', 'amount', 'profit', 'streak'];

    public function __construct(BetService $betService)
    {
        parent::__construct();
        $this->betService = $betService;
    }

    public function handle()
    {
        $this->info('Starting betting bot...');
        Log::info('Starting betting bot run');

        try {
            // Get bets to process
            $bets = $this->getBetsToProcess();
            
            if ($bets->isEmpty()) {
                $this->info('No bets to process');
                return Command::SUCCESS;
            }

            $this->info("Found {$bets->count()} bets to process");
            
            // Create progress bar
            $progressBar = $this->output->createProgressBar($bets->count());
            $progressBar->start();

            $stats = $this->initializeStats();

            foreach ($bets as $bet) {
                try {
                    $this->processBet($bet, $stats, $progressBar);
                } catch (\Exception $e) {
                    $this->handleBetError($bet, $e, $stats);
                }
                
                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Sort statistics
            $this->sortStatistics($stats);

            // Show or export statistics
            $format = $this->option('format');
            if ($exportPath = $this->option('export')) {
                $this->exportStatistics($stats, $exportPath);
            } else {
                $this->showStatistics($stats, $format);
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->handleGlobalError($e);
            return Command::FAILURE;
        }
    }

    protected function initializeStats()
    {
        return [
            'won' => 0,
            'lost' => 0,
            'errors' => 0,
            'total_win_amount' => 0,
            'total_bet_amount' => 0,
            'best_bet' => null,
            'worst_bet' => null,
            'current_streak' => 0,
            'longest_win_streak' => 0,
            'longest_loss_streak' => 0,
            'hourly_stats' => [],
            'daily_stats' => [],
            'weekly_stats' => [],
            'monthly_stats' => [],
            'yearly_stats' => [],
            'bet_type_stats' => []
        ];
    }

    protected function getBetsToProcess()
    {
        $query = $this->betService->getBetsQuery();

        if ($betId = $this->option('bet-id')) {
            return $this->betService->getBet($betId) ? collect([$this->betService->getBet($betId)]) : collect();
        }

        if ($date = $this->option('date')) {
            $query->whereDate('created_at', Carbon::parse($date));
        }

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    protected function processBet($bet, &$stats, $progressBar)
    {
        $this->info("\nChecking status for bet: {$bet->match_name}");
        Log::info("Processing bet", [
            'bet_id' => $bet->id,
            'match' => $bet->match_name,
            'type' => $bet->type,
            'amount' => $bet->amount
        ]);

        if ($this->option('dry-run')) {
            $this->info("<comment>[DRY RUN]</comment> Would check status for bet: {$bet->match_name}");
            return;
        }

        $result = $this->betService->checkBetStatus($bet, $this->option('force'));
                
                if ($result['success']) {
            $this->updateStats($bet, $stats);
            $this->showBetResult($bet);
        } else {
            $this->handleBetError($bet, new \Exception($result['message']), $stats);
        }
    }

    protected function updateStats($bet, &$stats)
    {
        $date = Carbon::parse($bet->created_at);
        
        // Update time-based stats
        $this->updateTimeBasedStats($bet, $date, $stats);
        
        // Update bet type stats
        $this->updateBetTypeStats($bet, $stats);

                    if ($bet->isWon()) {
            $stats['won']++;
            $stats['total_win_amount'] += $bet->actual_win;
            $stats['current_streak'] = max(0, $stats['current_streak']) + 1;
            $stats['longest_win_streak'] = max($stats['longest_win_streak'], $stats['current_streak']);
            
            // Update best bet
            if (!$stats['best_bet'] || $bet->actual_win > $stats['best_bet']->actual_win) {
                $stats['best_bet'] = $bet;
                    }
                } else {
            $stats['lost']++;
            $stats['current_streak'] = min(0, $stats['current_streak']) - 1;
            $stats['longest_loss_streak'] = max($stats['longest_loss_streak'], abs($stats['current_streak']));
            
            // Update worst bet
            if (!$stats['worst_bet'] || $bet->amount > $stats['worst_bet']->amount) {
                $stats['worst_bet'] = $bet;
            }
        }

        $stats['total_bet_amount'] += $bet->amount;
    }

    protected function updateTimeBasedStats($bet, $date, &$stats)
    {
        // Hourly stats
        $hourKey = $date->format('Y-m-d H');
        if (!isset($stats['hourly_stats'][$hourKey])) {
            $stats['hourly_stats'][$hourKey] = ['won' => 0, 'lost' => 0, 'amount' => 0];
        }
        
        // Daily stats
        $dayKey = $date->format('Y-m-d');
        if (!isset($stats['daily_stats'][$dayKey])) {
            $stats['daily_stats'][$dayKey] = ['won' => 0, 'lost' => 0, 'amount' => 0];
        }
        
        // Weekly stats
        $weekKey = $date->format('Y-W');
        if (!isset($stats['weekly_stats'][$weekKey])) {
            $stats['weekly_stats'][$weekKey] = ['won' => 0, 'lost' => 0, 'amount' => 0];
        }
        
        // Monthly stats
        $monthKey = $date->format('Y-m');
        if (!isset($stats['monthly_stats'][$monthKey])) {
            $stats['monthly_stats'][$monthKey] = ['won' => 0, 'lost' => 0, 'amount' => 0];
        }
        
        // Yearly stats
        $yearKey = $date->format('Y');
        if (!isset($stats['yearly_stats'][$yearKey])) {
            $stats['yearly_stats'][$yearKey] = ['won' => 0, 'lost' => 0, 'amount' => 0];
        }

        // Update stats
        $timeStats = [
            'hourly_stats' => $hourKey,
            'daily_stats' => $dayKey,
            'weekly_stats' => $weekKey,
            'monthly_stats' => $monthKey,
            'yearly_stats' => $yearKey
        ];

        foreach ($timeStats as $statKey => $timeKey) {
            if ($bet->isWon()) {
                $stats[$statKey][$timeKey]['won']++;
            } else {
                $stats[$statKey][$timeKey]['lost']++;
            }
            $stats[$statKey][$timeKey]['amount'] += $bet->amount;
        }
    }

    protected function updateBetTypeStats($bet, &$stats)
    {
        $type = $bet->type ?? 'unknown';
        if (!isset($stats['bet_type_stats'][$type])) {
            $stats['bet_type_stats'][$type] = [
                'won' => 0,
                'lost' => 0,
                'amount' => 0,
                'win_amount' => 0
            ];
        }

        if ($bet->isWon()) {
            $stats['bet_type_stats'][$type]['won']++;
            $stats['bet_type_stats'][$type]['win_amount'] += $bet->actual_win;
        } else {
            $stats['bet_type_stats'][$type]['lost']++;
        }
        $stats['bet_type_stats'][$type]['amount'] += $bet->amount;
    }

    protected function showBetResult($bet)
    {
        if ($bet->isWon()) {
            $this->info("<fg=green>✅ Bet won! Amount: {$bet->actual_win}</>");
        } else {
            $this->info("<fg=red>❌ Bet lost! Amount: {$bet->amount}</>");
        }
    }

    protected function handleBetError($bet, $e, &$stats)
    {
        $stats['errors']++;
        $errorMessage = "Error checking bet status: {$e->getMessage()}";
        
        $this->error("<fg=red>⚠️ {$errorMessage}</>");
        Log::error($errorMessage, [
            'bet_id' => $bet->id,
            'match' => $bet->match_name,
            'type' => $bet->type,
            'amount' => $bet->amount,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    protected function handleGlobalError($e)
    {
        $errorMessage = 'Error running betting bot: ' . $e->getMessage();
        
        $this->error("<fg=red>🚨 {$errorMessage}</>");
        Log::error($errorMessage, [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }

    protected function sortStatistics(&$stats)
    {
        $sortBy = $this->option('sort');
        if (!in_array($sortBy, $this->supportedSorts)) {
            return;
        }

        $sortFunction = function($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'date':
                    return strtotime($a) - strtotime($b);
                case 'amount':
                    return $b['amount'] - $a['amount'];
                case 'profit':
                    return ($b['win_amount'] - $b['amount']) - ($a['win_amount'] - $a['amount']);
                case 'streak':
                    return $b['won'] - $a['won'];
                default:
                    return 0;
            }
        };

        // Sort time-based stats
        foreach (['hourly_stats', 'daily_stats', 'weekly_stats', 'monthly_stats', 'yearly_stats'] as $statKey) {
            uasort($stats[$statKey], $sortFunction);
        }

        // Sort bet type stats
        uasort($stats['bet_type_stats'], $sortFunction);
    }

    protected function showStatistics($stats, $format = 'table')
    {
        if (!in_array($format, $this->supportedFormats)) {
            $this->error("Unsupported format: {$format}");
            return;
        }

        switch ($format) {
            case 'table':
                $this->showTableStatistics($stats);
                break;
            case 'json':
                $this->info(json_encode($stats, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                $this->info($this->convertStatsToCsv($stats));
                break;
        }
    }

    protected function showTableStatistics($stats)
    {
        $totalBets = $stats['won'] + $stats['lost'];
        $winRate = $totalBets > 0 ? ($stats['won'] / $totalBets) * 100 : 0;
        $averageBetAmount = $totalBets > 0 ? $stats['total_bet_amount'] / $totalBets : 0;
        $profit = $stats['total_win_amount'] - $stats['total_bet_amount'];

        $this->info("\n<fg=cyan>📊 Detailed Betting Statistics:</>");
        $this->info("<fg=cyan>----------------------------------------</>");
        $this->info("<fg=white>Total Bets: {$totalBets}</>");
        $this->info("<fg=green>Won: {$stats['won']}</>");
        $this->info("<fg=red>Lost: {$stats['lost']}</>");
        $this->info("<fg=yellow>Errors: {$stats['errors']}</>");
        $this->info("<fg=cyan>----------------------------------------</>");
        $this->info("<fg=white>Win Rate: " . number_format($winRate, 2) . "%</>");
        $this->info("<fg=white>Average Bet Amount: " . number_format($averageBetAmount, 2) . "</>");
        $this->info("<fg=white>Total Bet Amount: " . number_format($stats['total_bet_amount'], 2) . "</>");
        $this->info("<fg=green>Total Win Amount: " . number_format($stats['total_win_amount'], 2) . "</>");
        $this->info("<fg=" . ($profit >= 0 ? 'green' : 'red') . ">Profit: " . number_format($profit, 2) . "</>");
        $this->info("<fg=cyan>----------------------------------------</>");

        // Streaks
        $this->info("\n<fg=cyan>📈 Streaks:</>");
        $this->info("<fg=green>Longest Win Streak: {$stats['longest_win_streak']}</>");
        $this->info("<fg=red>Longest Loss Streak: {$stats['longest_loss_streak']}</>");
        $this->info("<fg=white>Current Streak: " . ($stats['current_streak'] >= 0 ? '+' : '') . "{$stats['current_streak']}</>");

        // Best/Worst Bets
        if ($stats['best_bet']) {
            $this->info("\n<fg=green>🏆 Best Bet:</>");
            $this->info("<fg=white>Match: {$stats['best_bet']->match_name}</>");
            $this->info("<fg=white>Amount: {$stats['best_bet']->amount}</>");
            $this->info("<fg=green>Win: {$stats['best_bet']->actual_win}</>");
        }

        if ($stats['worst_bet']) {
            $this->info("\n<fg=red>📉 Worst Bet:</>");
            $this->info("<fg=white>Match: {$stats['worst_bet']->match_name}</>");
            $this->info("<fg=white>Amount: {$stats['worst_bet']->amount}</>");
        }

        // Time-based Stats
        $this->showTimeBasedStats($stats);

        // Bet Type Stats
        $this->showBetTypeStats($stats);
    }

    protected function showTimeBasedStats($stats)
    {
        $this->info("\n<fg=cyan>📅 Time-based Statistics:</>");
        
        // Hourly Stats
        $this->info("\n<fg=cyan>⏰ Hourly Stats:</>");
        foreach ($stats['hourly_stats'] as $hour => $hourStats) {
            $this->info("<fg=white>{$hour}:</>");
            $this->info("  <fg=green>Won: {$hourStats['won']}</>");
            $this->info("  <fg=red>Lost: {$hourStats['lost']}</>");
            $this->info("  <fg=white>Amount: " . number_format($hourStats['amount'], 2) . "</>");
        }

        // Daily Stats
        $this->info("\n<fg=cyan>📅 Daily Stats:</>");
        foreach ($stats['daily_stats'] as $date => $dayStats) {
            $this->info("<fg=white>{$date}:</>");
            $this->info("  <fg=green>Won: {$dayStats['won']}</>");
            $this->info("  <fg=red>Lost: {$dayStats['lost']}</>");
            $this->info("  <fg=white>Amount: " . number_format($dayStats['amount'], 2) . "</>");
        }

        // Weekly Stats
        $this->info("\n<fg=cyan>📅 Weekly Stats:</>");
        foreach ($stats['weekly_stats'] as $week => $weekStats) {
            $this->info("<fg=white>Week {$week}:</>");
            $this->info("  <fg=green>Won: {$weekStats['won']}</>");
            $this->info("  <fg=red>Lost: {$weekStats['lost']}</>");
            $this->info("  <fg=white>Amount: " . number_format($weekStats['amount'], 2) . "</>");
        }

        // Monthly Stats
        $this->info("\n<fg=cyan>📅 Monthly Stats:</>");
        foreach ($stats['monthly_stats'] as $month => $monthStats) {
            $this->info("<fg=white>{$month}:</>");
            $this->info("  <fg=green>Won: {$monthStats['won']}</>");
            $this->info("  <fg=red>Lost: {$monthStats['lost']}</>");
            $this->info("  <fg=white>Amount: " . number_format($monthStats['amount'], 2) . "</>");
        }

        // Yearly Stats
        $this->info("\n<fg=cyan>📅 Yearly Stats:</>");
        foreach ($stats['yearly_stats'] as $year => $yearStats) {
            $this->info("<fg=white>{$year}:</>");
            $this->info("  <fg=green>Won: {$yearStats['won']}</>");
            $this->info("  <fg=red>Lost: {$yearStats['lost']}</>");
            $this->info("  <fg=white>Amount: " . number_format($yearStats['amount'], 2) . "</>");
        }
    }

    protected function showBetTypeStats($stats)
    {
        $this->info("\n<fg=cyan>🎯 Bet Type Statistics:</>");
        foreach ($stats['bet_type_stats'] as $type => $typeStats) {
            $this->info("\n<fg=white>Type: {$type}</>");
            $this->info("  <fg=green>Won: {$typeStats['won']}</>");
            $this->info("  <fg=red>Lost: {$typeStats['lost']}</>");
            $this->info("  <fg=white>Total Amount: " . number_format($typeStats['amount'], 2) . "</>");
            $this->info("  <fg=green>Win Amount: " . number_format($typeStats['win_amount'], 2) . "</>");
            $profit = $typeStats['win_amount'] - $typeStats['amount'];
            $this->info("  <fg=" . ($profit >= 0 ? 'green' : 'red') . ">Profit: " . number_format($profit, 2) . "</>");
        }
    }

    protected function exportStatistics($stats, $path)
    {
        $format = pathinfo($path, PATHINFO_EXTENSION);
        
        switch ($format) {
            case 'json':
                File::put($path, json_encode($stats, JSON_PRETTY_PRINT));
                break;
            case 'csv':
                File::put($path, $this->convertStatsToCsv($stats));
                break;
            case 'xlsx':
                $this->exportToExcel($stats, $path);
                break;
            case 'pdf':
                $this->exportToPdf($stats, $path);
                break;
            default:
                $this->error("Unsupported export format: {$format}");
                return;
        }

        $this->info("\n<fg=green>✅ Statistics exported to: {$path}</>");
    }

    protected function convertStatsToCsv($stats)
    {
        $rows = [];
        
        // Basic stats
        $rows[] = ['Category', 'Value'];
        $rows[] = ['Total Bets', $stats['won'] + $stats['lost']];
        $rows[] = ['Won', $stats['won']];
        $rows[] = ['Lost', $stats['lost']];
        $rows[] = ['Errors', $stats['errors']];
        $rows[] = ['Total Win Amount', $stats['total_win_amount']];
        $rows[] = ['Total Bet Amount', $stats['total_bet_amount']];
        $rows[] = ['Profit', $stats['total_win_amount'] - $stats['total_bet_amount']];
        $rows[] = ['Longest Win Streak', $stats['longest_win_streak']];
        $rows[] = ['Longest Loss Streak', $stats['longest_loss_streak']];
        
        // Time-based stats
        foreach (['hourly_stats', 'daily_stats', 'weekly_stats', 'monthly_stats', 'yearly_stats'] as $statKey) {
            $rows[] = [];
            $rows[] = [ucfirst(str_replace('_stats', '', $statKey)) . ' Stats'];
            $rows[] = ['Time', 'Won', 'Lost', 'Amount'];
            foreach ($stats[$statKey] as $time => $timeStats) {
                $rows[] = [$time, $timeStats['won'], $timeStats['lost'], $timeStats['amount']];
            }
        }
        
        // Bet type stats
        $rows[] = [];
        $rows[] = ['Bet Type Stats'];
        $rows[] = ['Type', 'Won', 'Lost', 'Amount', 'Win Amount', 'Profit'];
        foreach ($stats['bet_type_stats'] as $type => $typeStats) {
            $profit = $typeStats['win_amount'] - $typeStats['amount'];
            $rows[] = [
                $type,
                $typeStats['won'],
                $typeStats['lost'],
                $typeStats['amount'],
                $typeStats['win_amount'],
                $profit
            ];
        }
        
        return collect($rows)->map(function ($row) {
            return implode(',', $row);
        })->implode("\n");
    }

    protected function exportToExcel($stats, $path)
    {
        $spreadsheet = new Spreadsheet();
        
        // Basic stats
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Basic Stats');
        $sheet->fromArray([
            ['Category', 'Value'],
            ['Total Bets', $stats['won'] + $stats['lost']],
            ['Won', $stats['won']],
            ['Lost', $stats['lost']],
            ['Errors', $stats['errors']],
            ['Total Win Amount', $stats['total_win_amount']],
            ['Total Bet Amount', $stats['total_bet_amount']],
            ['Profit', $stats['total_win_amount'] - $stats['total_bet_amount']],
            ['Longest Win Streak', $stats['longest_win_streak']],
            ['Longest Loss Streak', $stats['longest_loss_streak']]
        ]);

        // Time-based stats
        foreach (['hourly_stats', 'daily_stats', 'weekly_stats', 'monthly_stats', 'yearly_stats'] as $statKey) {
            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle(ucfirst(str_replace('_stats', '', $statKey)));
            $sheet->fromArray([
                ['Time', 'Won', 'Lost', 'Amount']
            ]);
            $row = 2;
            foreach ($stats[$statKey] as $time => $timeStats) {
                $sheet->fromArray([
                    [$time, $timeStats['won'], $timeStats['lost'], $timeStats['amount']]
                ], null, "A{$row}");
                $row++;
            }
        }

        // Bet type stats
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Bet Types');
        $sheet->fromArray([
            ['Type', 'Won', 'Lost', 'Amount', 'Win Amount', 'Profit']
        ]);
        $row = 2;
        foreach ($stats['bet_type_stats'] as $type => $typeStats) {
            $profit = $typeStats['win_amount'] - $typeStats['amount'];
            $sheet->fromArray([
                [$type, $typeStats['won'], $typeStats['lost'], $typeStats['amount'], $typeStats['win_amount'], $profit]
            ], null, "A{$row}");
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
    }

    protected function exportToPdf($stats, $path)
    {
        $pdf = PDF::loadView('betting.stats', [
            'stats' => $stats,
            'totalBets' => $stats['won'] + $stats['lost'],
            'winRate' => $totalBets > 0 ? ($stats['won'] / $totalBets) * 100 : 0,
            'averageBetAmount' => $totalBets > 0 ? $stats['total_bet_amount'] / $totalBets : 0,
            'profit' => $stats['total_win_amount'] - $stats['total_bet_amount']
        ]);

        $pdf->save($path);
    }
} 