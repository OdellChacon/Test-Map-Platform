<?php

use App\Enums\ReportStatus;
use App\Models\OutageReport;
use App\Models\User;

function validReportPayload(array $overrides = []): array
{
    return array_merge([
        'latitude' => 40.4168,
        'longitude' => -3.7038,
        'radius_km' => 3,
        'duration_type' => 'fixed',
        'duration_hours' => 6,
        'description' => 'No power since this morning.',
    ], $overrides);
}

test('a guest cannot create a report', function () {
    $this->postJson('/api/v1/reports', validReportPayload())->assertUnauthorized();
});

test('an authenticated user can create a fixed-duration report', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/reports', validReportPayload(['duration_hours' => 6]));

    $response->assertCreated()
        ->assertJsonPath('data.status', ReportStatus::Active->value)
        ->assertJsonPath('data.duration_type', 'fixed')
        ->assertJsonPath('data.latitude', 40.4168)
        ->assertJsonPath('data.longitude', -3.7038);

    $report = OutageReport::query()->firstOrFail();
    expect($report->expires_at->diffInMinutes(now()->addHours(6)))->toBeLessThan(2);
});

test('an until-closed report expires after the configured safety cap', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/reports', validReportPayload([
        'duration_type' => 'until_closed',
        'duration_hours' => null,
    ]))->assertCreated();

    $report = OutageReport::query()->firstOrFail();
    $expectedExpiry = now()->addHours((int) config('outage.max_report_duration_hours'));

    expect($report->duration_hours)->toBeNull();
    expect($report->expires_at->diffInMinutes($expectedExpiry))->toBeLessThan(2);
});

test('creating a report requires valid coordinates and radius', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/reports', validReportPayload(['latitude' => 120, 'radius_km' => 100]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'radius_km']);
});

test('a fixed-duration report requires duration hours within the safety cap', function () {
    $user = User::factory()->create();
    $max = (int) config('outage.max_report_duration_hours');

    $this->actingAs($user)
        ->postJson('/api/v1/reports', validReportPayload(['duration_hours' => $max + 1]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duration_hours');
});

test('a user cannot exceed the maximum number of active reports', function () {
    $user = User::factory()->create();
    $max = (int) config('outage.max_active_reports_per_user');

    OutageReport::factory()->count($max)->for($user)->create();

    $this->actingAs($user)
        ->postJson('/api/v1/reports', validReportPayload())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reports');
});

test('report creation is rate limited', function () {
    $user = User::factory()->create();
    $attempts = (int) config('outage.report_rate_limit.attempts');

    for ($i = 0; $i < $attempts; $i++) {
        OutageReport::query()->delete();

        $this->actingAs($user)
            ->postJson('/api/v1/reports', validReportPayload())
            ->assertCreated();
    }

    $this->actingAs($user)
        ->postJson('/api/v1/reports', validReportPayload())
        ->assertStatus(429);
});
