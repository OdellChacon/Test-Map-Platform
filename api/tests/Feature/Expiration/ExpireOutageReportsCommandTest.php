<?php

use App\Enums\ReportStatus;
use App\Models\OutageReport;

test('it expires active reports past their expiry time', function () {
    $pastDue = OutageReport::factory()->create(['expires_at' => now()->subHour()]);
    $stillActive = OutageReport::factory()->create(['expires_at' => now()->addHour()]);
    $alreadyClosed = OutageReport::factory()->closed()->create(['expires_at' => now()->subHour()]);

    $this->artisan('outage:expire-reports')->assertSuccessful();

    expect($pastDue->refresh()->status)->toBe(ReportStatus::Expired);
    expect($stillActive->refresh()->status)->toBe(ReportStatus::Active);
    expect($alreadyClosed->refresh()->status)->toBe(ReportStatus::Closed);
});
