<?php

use App\Enums\ReportStatus;
use App\Models\OutageReport;
use App\Models\User;

test('an owner can close their own active report', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/api/v1/reports/{$report->id}/close")
        ->assertOk()
        ->assertJsonPath('data.status', ReportStatus::Closed->value);

    $report->refresh();
    expect($report->status)->toBe(ReportStatus::Closed);
    expect($report->closed_at)->not->toBeNull();
});

test('a user cannot close another user\'s report', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create();

    $this->actingAs($otherUser)
        ->postJson("/api/v1/reports/{$report->id}/close")
        ->assertForbidden();
});

test('an already closed report cannot be closed again', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->closed()->for($user)->create();

    $this->actingAs($user)
        ->postJson("/api/v1/reports/{$report->id}/close")
        ->assertStatus(422);
});

test('a guest cannot close a report', function () {
    $report = OutageReport::factory()->create();

    $this->postJson("/api/v1/reports/{$report->id}/close")->assertUnauthorized();
});
