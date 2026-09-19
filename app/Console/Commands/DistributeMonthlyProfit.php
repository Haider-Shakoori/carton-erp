<?php
// app/Console/Commands/DistributeMonthlyProfit.php

namespace App\Console\Commands;

use App\Services\ProfitSharingService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class DistributeMonthlyProfit extends Command
{
    protected $signature = 'profit:distribute-monthly {--month= : Month to distribute (1-12)} {--year= : Year to distribute} {--user= : User ID for created_by}';
    protected $description = 'Distribute monthly profit/loss to shareholders';

    protected $profitService;

    public function __construct(ProfitSharingService $profitService)
    {
        parent::__construct();
        $this->profitService = $profitService;
    }

    public function handle()
    {
        $month = $this->option('month') ?? Carbon::now()->month;
        $year = $this->option('year') ?? Carbon::now()->year;
        $userId = $this->option('user') ?? 1;

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth();

        $this->info("Distributing profit for: {$startDate->format('M Y')}");
        $this->line("----------------------------------------");

        try {
            // Calculate profit first
            $profitData = $this->profitService->calculatePeriodProfit($startDate, $endDate);

            $this->info("📊 Business Summary:");
            $this->line("  Total Sales: " . number_format($profitData['total_sales'], 2));
            $this->line("  Total Purchases: " . number_format($profitData['total_purchases'], 2));
            $this->line("  Total Expenses: " . number_format($profitData['total_expenses'], 2));
            $this->line("  Sales Profit: " . number_format($profitData['sales_profit'], 2));
            $this->line("  Total Profit/Loss: " . number_format($profitData['total_profit'], 2));
            $this->line("");

            if ($profitData['total_profit'] == 0) {
                $this->warn("No profit to distribute. Skipping...");
                return Command::SUCCESS;
            }

            // Create distribution
            $distribution = $this->profitService->distributeProfitLoss(
                $startDate,
                $endDate,
                "Monthly distribution for {$startDate->format('F Y')}",
                $userId
            );

            $this->info("✅ Distribution created successfully!");
            $this->line("  Distribution Number: {$distribution->distribution_number}");
            $this->line("  Total Profit/Loss: " . number_format($distribution->total_profit, 2));
            $this->line("");

            $this->info("📋 Shareholder Distribution:");
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
