<?php

use App\Models\OutageReport;
use App\Models\User;

test("a user can confirm another user's active report", function () {
    $owner = User::factory()->create();
    $confirmer = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create();

    $this->actingAs($confirmer)
        ->postJson("/api/v1/reports/{$report->id}/confirm")
        ->assertOk()
        ->assertJsonPath('data.confirmations_count', 1);

    $this->assertDatabaseHas('outage_report_confirmations', [
        'outage_report_id' => $report->id,
        'user_id' => $confirmer->id,
    ]);
});

test('a user cannot confirm the same report twice', function () {
    $owner = User::factory()->create();
    $confirmer = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create();

    $this->actingAs($confirmer)->postJson("/api/v1/reports/{$report->id}/confirm")->assertOk();

    $this->actingAs($confirmer)
        ->postJson("/api/v1/reports/{$report->id}/confirm")
        ->assertStatus(422);
});

test('a user cannot confirm their own report', function () {
    $owner = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create();

    $this->actingAs($owner)
        ->postJson("/api/v1/reports/{$report->id}/confirm")
        ->assertForbidden();
});

test('a closed report cannot be confirmed', function () {
    $owner = User::factory()->create();
    $confirmer = User::factory()->create();
    $report = OutageReport::factory()->closed()->for($owner)->create();

    $this->actingAs($confirmer)
        ->postJson("/api/v1/reports/{$report->id}/confirm")
        ->assertStatus(422);
});

test('a guest cannot confirm a report', function () {
    $report = OutageReport::factory()->create();

    $this->postJson("/api/v1/reports/{$report->id}/confirm")->assertUnauthorized();
});

test('a report past its expiry cannot be confirmed, even if the scheduler has not run yet', function () {
    $owner = User::factory()->create();
    $confirmer = User::factory()->create();
    $report = OutageReport::factory()->for($owner)->create(['expires_at' => now()->subMinute()]);

    $this->actingAs($confirmer)
        ->postJson("/api/v1/reports/{$report->id}/confirm")
        ->assertStatus(422);
});
