<?php

namespace App\Console\Commands;

use App\Services\FactoryReleaseAuditService;
use Illuminate\Console\Command;

class FactoryReleaseAudit extends Command
{
    protected $signature = 'erp:release-audit
        {--json : Print a machine-readable audit report}
        {--fail-on-risk : Fail the exit code when unresolved risks exist}';

    protected $description = 'Read-only audit of factory BOM reviews, physical COGS and historic shareholder allocation status.';

    public function handle(FactoryReleaseAuditService $audit): int
    {
        $report = $audit->report();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
        } else {
            $this->table(
                ['Release gate', 'Outstanding'],
                collect($report['counts'])->map(
                    fn ($count, $key) => [
                        str_replace('_', ' ', ucfirst($key)),
                        $count,
                    ]
                )->values()->all()
            );
            $this->line('Status: '.$report['status']);
            $this->warn('Review-only: missing factory and historical bank evidence cannot be inferred from GitHub.');
        }

        if ($this->option('fail-on-risk') && $report['status'] === 'review_required') {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
