<?php

use App\Models\OutageReport;
use App\Models\User;

test('a guest cannot list reports', function () {
    $this->getJson('/api/v1/reports')->assertUnauthorized();
});

test('a user only sees their own reports', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    OutageReport::factory()->count(2)->for($owner)->create();
    OutageReport::factory()->count(3)->for($otherUser)->create();

    $response = $this->actingAs($owner)->getJson('/api/v1/reports')->assertOk();

    expect($response->json('data'))->toHaveCount(2);
});

test('the reports list is paginated', function () {
    $user = User::factory()->create();
    OutageReport::factory()->count(20)->for($user)->create();

    $response = $this->actingAs($user)->getJson('/api/v1/reports')->assertOk();

    expect($response->json('data'))->toHaveCount(15);
    expect($response->json('meta.total'))->toBe(20);
});
