<?php

use App\Models\OutageReport;

test('the stats endpoint requires no authentication', function () {
    $this->getJson('/api/v1/public/stats')->assertOk();
});

test('it reports totals and per-day counts', function () {
    OutageReport::factory()->count(2)->create();
    OutageReport::factory()->closed()->create();

    $response = $this->getJson('/api/v1/public/stats')->assertOk();

    expect($response->json('data.total_reports'))->toBe(3)
        ->and($response->json('data.active_reports'))->toBe(2)
        ->and($response->json('data.reports_per_day'))->toBeArray();
});
