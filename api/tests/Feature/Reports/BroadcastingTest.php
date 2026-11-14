<?php

use App\Events\PublicOutageReportChanged;
use App\Http\Resources\PublicOutageReportResource;
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
        'duration_type' => 'until_closed',
        'description' => 'No power on the whole block',
    ])->assertCreated();

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'created'
            && $event->report['duration_type'] === 'until_closed'
            && $event->report['description'] === 'No power on the whole block',
    );
});

test('every broadcast payload carries the same fields the public map marker needs', function () {
    // Regression guard: the live map rebuilds a marker entirely from this payload (see
    // PublicMapView.vue's handleReportChanged), so a field present on PublicOutageReportResource
    // but missing here silently disappears from the map on the next real-time update instead of
    // failing loudly - this is exactly what happened with duration_type and description.
    Event::fake([PublicOutageReportChanged::class]);

    $owner = User::factory()->create();
    $confirmer = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create(['description' => 'Still out']);

    $this->actingAs($confirmer)->postJson("/api/v1/reports/{$report->id}/confirm")->assertOk();

    $expectedKeys = collect((new PublicOutageReportResource($report->fresh()))->resolve(request()))
        ->keys()
        ->reject(fn ($key) => $key === 'id')
        ->sort()
        ->values();

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'updated'
            && collect($event->report)->keys()->sort()->values()->all() === $expectedKeys->all()
            && $event->report['description'] === 'Still out',
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
