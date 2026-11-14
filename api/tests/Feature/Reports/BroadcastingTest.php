<?php

use App\Events\PublicOutageReportChanged;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Support\Facades\Event;

test('creating a report broadcasts a created event', function () {
    Event::fake([PublicOutageReportChanged::class]);

    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/reports', [
        'latitude' => 40.4168,
        'longitude' => -3.7038,
        'radius_km' => 2,
        'duration_type' => 'fixed',
        'duration_hours' => 4,
    ])->assertCreated();

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'created',
    );
});

test('closing a report broadcasts a removed event', function () {
    Event::fake([PublicOutageReportChanged::class]);

    $user = User::factory()->create();
    $report = OutageReport::factory()->for($user)->create();

    $this->actingAs($user)->postJson("/api/v1/reports/{$report->id}/close")->assertOk();

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'removed' && $event->reportId === $report->id,
    );
});

test('confirming a report broadcasts an updated event with the new count', function () {
    Event::fake([PublicOutageReportChanged::class]);

    $owner = User::factory()->create();
    $confirmer = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create();

    $this->actingAs($confirmer)->postJson("/api/v1/reports/{$report->id}/confirm")->assertOk();

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'updated' && $event->report['confirmations_count'] === 1,
    );
});

test('the expiration job broadcasts a removed event for each expired report', function () {
    Event::fake([PublicOutageReportChanged::class]);

    $report = OutageReport::factory()->create(['expires_at' => now()->subHour()]);

    $this->artisan('outage:expire-reports')->assertSuccessful();

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'removed' && $event->reportId === $report->id,
    );
});
