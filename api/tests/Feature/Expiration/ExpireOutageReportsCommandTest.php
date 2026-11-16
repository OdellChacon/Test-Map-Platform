<?php

use App\Enums\ReportDurationType;
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

test('an until-closed report stays active right up to the 72h safety cap and expires once it passes', function () {
    $maxHours = config('outage.max_report_duration_hours');

    $report = OutageReport::factory()->create([
        'duration_type' => ReportDurationType::UntilClosed,
        'duration_hours' => null,
        'expires_at' => now()->addHours($maxHours),
    ]);

    $this->travel($maxHours - 1)->hours();
    $this->artisan('outage:expire-reports')->assertSuccessful();
    expect($report->refresh()->status)->toBe(ReportStatus::Active);

    $this->travel(2)->hours();
    $this->artisan('outage:expire-reports')->assertSuccessful();
    expect($report->refresh()->status)->toBe(ReportStatus::Expired);
});

test('a fixed-duration report expires at its own duration, not the 72h safety cap', function () {
    $report = OutageReport::factory()->create([
        'duration_type' => ReportDurationType::Fixed,
        'duration_hours' => 4,
        'expires_at' => now()->addHours(4),
    ]);

    $this->travel(3)->hours();
    $this->artisan('outage:expire-reports')->assertSuccessful();
    expect($report->refresh()->status)->toBe(ReportStatus::Active);

    $this->travel(2)->hours();
    $this->artisan('outage:expire-reports')->assertSuccessful();
    expect($report->refresh()->status)->toBe(ReportStatus::Expired);
});
