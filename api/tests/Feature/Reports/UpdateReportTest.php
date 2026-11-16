<?php

use App\Enums\ReportDurationType;
use App\Models\OutageReport;
use App\Models\User;

test('an owner can update the radius and description of an active report', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->for($user)->create(['radius_km' => 2]);

    $this->actingAs($user)
        ->patchJson("/api/v1/reports/{$report->id}", [
            'radius_km' => 8,
            'description' => 'Updated description.',
        ])
        ->assertOk()
        ->assertJsonPath('data.radius_km', 8)
        ->assertJsonPath('data.description', 'Updated description.');
});

test('an owner can change the duration of a fixed report', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->for($user)->create([
        'duration_type' => ReportDurationType::Fixed,
        'duration_hours' => 4,
    ]);

    $this->actingAs($user)
        ->patchJson("/api/v1/reports/{$report->id}", ['duration_hours' => 10])
        ->assertOk();

    $report->refresh();
    expect($report->duration_hours)->toBe(10);
    expect($report->expires_at->diffInMinutes($report->created_at->clone()->addHours(10)))->toBeLessThan(2);
});

test('duration hours cannot be changed on an until-closed report', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->for($user)->create([
        'duration_type' => ReportDurationType::UntilClosed,
        'duration_hours' => null,
    ]);

    $this->actingAs($user)
        ->patchJson("/api/v1/reports/{$report->id}", ['duration_hours' => 10])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('duration_hours');
});

test('a user cannot update another user\'s report', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->patchJson("/api/v1/reports/{$report->id}", ['radius_km' => 5])
        ->assertForbidden();
});

test('a closed report cannot be edited', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->closed()->for($user)->create();

    $this->actingAs($user)
        ->patchJson("/api/v1/reports/{$report->id}", ['radius_km' => 5])
        ->assertStatus(422);
});

test('a guest cannot update a report', function () {
    $report = OutageReport::factory()->create();

    $this->patchJson("/api/v1/reports/{$report->id}", ['radius_km' => 5])->assertUnauthorized();
});

test('a report past its expiry cannot be revived by editing it, even if the scheduler has not run yet', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->for($user)->create([
        'duration_type' => ReportDurationType::Fixed,
        'duration_hours' => 4,
        'expires_at' => now()->subMinute(),
    ]);

    $this->actingAs($user)
        ->patchJson("/api/v1/reports/{$report->id}", ['duration_hours' => 72])
        ->assertStatus(422);

    $report->refresh();
    expect($report->duration_hours)->toBe(4);
    expect($report->expires_at->isPast())->toBeTrue();
});
