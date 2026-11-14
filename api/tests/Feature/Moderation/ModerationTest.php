<?php

use App\Enums\ReportStatus;
use App\Models\OutageReport;
use App\Models\User;

test('a moderator can list active reports for moderation', function () {
    $moderator = User::factory()->create(['is_moderator' => true]);
    OutageReport::factory()->count(3)->create();

    $this->actingAs($moderator)
        ->getJson('/api/v1/moderation/reports')
        ->assertOk();
});

test('a non-moderator cannot list reports for moderation', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/moderation/reports')
        ->assertForbidden();
});

test('a moderator can hide a report and it leaves an audit trail', function () {
    $moderator = User::factory()->create(['is_moderator' => true]);
    $report = OutageReport::factory()->create();

    $this->actingAs($moderator)
        ->postJson("/api/v1/moderation/reports/{$report->id}", [
            'action' => 'hide',
            'reason' => 'Spam report',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', ReportStatus::Hidden->value);

    $this->assertDatabaseHas('report_moderation_actions', [
        'outage_report_id' => $report->id,
        'moderator_id' => $moderator->id,
        'action' => 'hide',
        'reason' => 'Spam report',
    ]);
});

test('a moderator can close a report', function () {
    $moderator = User::factory()->create(['is_moderator' => true]);
    $report = OutageReport::factory()->create();

    $this->actingAs($moderator)
        ->postJson("/api/v1/moderation/reports/{$report->id}", ['action' => 'close'])
        ->assertOk()
        ->assertJsonPath('data.status', ReportStatus::Closed->value);
});

test('a non-moderator cannot moderate a report', function () {
    $user = User::factory()->create();
    $report = OutageReport::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/moderation/reports/{$report->id}", ['action' => 'hide'])
        ->assertForbidden();
});

test('a guest cannot access moderation endpoints', function () {
    $report = OutageReport::factory()->create();

    $this->getJson('/api/v1/moderation/reports')->assertUnauthorized();
    $this->postJson("/api/v1/moderation/reports/{$report->id}", ['action' => 'hide'])->assertUnauthorized();
});

test('an already moderated report cannot be moderated again', function () {
    $moderator = User::factory()->create(['is_moderator' => true]);
    $report = OutageReport::factory()->closed()->create();

    $this->actingAs($moderator)
        ->postJson("/api/v1/moderation/reports/{$report->id}", ['action' => 'hide'])
        ->assertStatus(422);
});

test('a hidden report does not appear on the public map', function () {
    $moderator = User::factory()->create(['is_moderator' => true]);
    $report = OutageReport::factory()->create();
    $report->setCoordinates(40.4168, -3.7038);

    $this->actingAs($moderator)
        ->postJson("/api/v1/moderation/reports/{$report->id}", ['action' => 'hide'])
        ->assertOk();

    $response = $this->getJson('/api/v1/public/reports?'.http_build_query([
        'south_west_lat' => 39,
        'south_west_lng' => -5,
        'north_east_lat' => 42,
        'north_east_lng' => -2,
    ]))->assertOk();

    expect($response->json('data'))->toBeEmpty();
});
