<?php

namespace App\Console\Commands;

use App\Enums\ReportStatus;
use App\Events\PublicOutageReportChanged;
use App\Models\OutageReport;
use Illuminate\Console\Command;

class ExpireOutageReports extends Command
{
    protected $signature = 'outage:expire-reports';

    protected $description = 'Marks active outage reports past their expiry time as expired';

    public function handle(): int
    {
        $expiredIds = OutageReport::query()
            ->where('status', ReportStatus::Active)
            ->where('expires_at', '<=', now())
            ->pluck('id');

        OutageReport::query()
            ->whereIn('id', $expiredIds)
            ->update(['status' => ReportStatus::Expired]);

        foreach ($expiredIds as $id) {
            PublicOutageReportChanged::dispatch('removed', $id);
        }

        $this->info("Expired {$expiredIds->count()} outage reports.");

        return self::SUCCESS;
    }
}
