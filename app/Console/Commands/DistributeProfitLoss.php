<?php
// app/Console/Commands/DistributeProfitLoss.php

namespace App\Console\Commands;

use App\Services\ProfitSharingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class DistributeProfitLoss extends Command
{
    protected $signature = 'profit:distribute {--start= : Start date (YYYY-MM-DD)} {--end= : End date (YYYY-MM-DD)} {--user= : User ID for created_by}';
    protected $description = 'Distribute profit/loss to shareholders based on share percentage';

    protected $profitService;

    public function __construct(ProfitSharingService $profitService)
    {
        parent::__construct();
        $this->profitService = $profitService;
    }

    public function handle()
    {
        $startDate = $this->option('start') ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->option('end') ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $userId = $this->option('user') ?? 1; // Default to user ID 1

        $this->info("Calculating profit/loss for period: {$startDate} to {$endDate}");

        try {
            $distribution = $this->profitService->distributeProfitLoss(
                Carbon::parse($startDate),
                Carbon::parse($endDate),
                "Auto-distribution for period {$startDate} to {$endDate}",
                $userId
            );

            $this->info("Distribution created successfully!");
            $this->info("Distribution Number: {$distribution->distribution_number}");
            $this->info("Total Profit/Loss: " . number_format($distribution->total_profit, 2));
            $this->info("Distributed to " . $distribution->items->count() . " shareholders");

            $this->table(
                ['Shareholder', 'Share %', 'Amount', 'Type'],
                $distribution->items->map(function ($item) {
                    return [
                        $item->shareholder->name,
                        $item->share_percentage . '%',
                        number_format($item->amount, 2),
                        $item->amount >= 0 ? 'Profit' : 'Loss',
                    ];
                })->toArray()
            );

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
