<?php

namespace App\Console\Commands;

use App\Enums\ReportStatus;
use App\Models\OutageReport;
use Illuminate\Console\Command;

class ExpireOutageReports extends Command
{
    protected $signature = 'outage:expire-reports';

    protected $description = 'Marks active outage reports past their expiry time as expired';

    public function handle(): int
    {
        $expired = OutageReport::query()
            ->where('status', ReportStatus::Active)
            ->where('expires_at', '<=', now())
            ->update(['status' => ReportStatus::Expired]);

        $this->info("Expired {$expired} outage reports.");

        return self::SUCCESS;
    }
}
