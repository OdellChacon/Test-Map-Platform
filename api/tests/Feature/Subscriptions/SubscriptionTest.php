<?php

use App\Jobs\NotifySubscribersOfNewOutage;
use App\Models\OutageSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Queue;

test('a user can create a subscription', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/subscriptions', [
        'latitude' => 40.4168,
        'longitude' => -3.7038,
        'radius_km' => 10,
    ])->assertCreated()
        ->assertJsonPath('data.latitude', 40.4168)
        ->assertJsonPath('data.radius_km', 10);

    $this->assertDatabaseHas('outage_subscriptions', ['user_id' => $user->id]);
});

test('a user can list only their own subscriptions', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    OutageSubscription::createWithCoordinates(['user_id' => $user->id, 'radius_km' => 5], 40.4168, -3.7038);
    OutageSubscription::createWithCoordinates(['user_id' => $otherUser->id, 'radius_km' => 5], 41.0, -4.0);

    $response = $this->actingAs($user)->getJson('/api/v1/subscriptions')->assertOk();

    expect($response->json('data'))->toHaveCount(1);
});

test('a user can delete their own subscription', function () {
    $user = User::factory()->create();
    $subscription = OutageSubscription::createWithCoordinates(
        ['user_id' => $user->id, 'radius_km' => 5], 40.4168, -3.7038
    );

    $this->actingAs($user)
        ->deleteJson("/api/v1/subscriptions/{$subscription->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('outage_subscriptions', ['id' => $subscription->id]);
});

test('a user cannot delete another user\'s subscription', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $subscription = OutageSubscription::createWithCoordinates(
        ['user_id' => $owner->id, 'radius_km' => 5], 40.4168, -3.7038
    );

    $this->actingAs($otherUser)
        ->deleteJson("/api/v1/subscriptions/{$subscription->id}")
        ->assertForbidden();
});

test('a guest cannot manage subscriptions', function () {
    $this->getJson('/api/v1/subscriptions')->assertUnauthorized();
    $this->postJson('/api/v1/subscriptions', ['latitude' => 1, 'longitude' => 1, 'radius_km' => 5])
        ->assertUnauthorized();
});

test('the notify job notifies a subscriber whose zone covers the new report', function () {
    $reporter = User::factory()->create();
    $subscriber = User::factory()->create();

    OutageSubscription::createWithCoordinates(
        ['user_id' => $subscriber->id, 'radius_km' => 20], 40.4168, -3.7038
    );

    (new NotifySubscribersOfNewOutage(1, 40.42, -3.70, 2, $reporter->id))->handle();

    expect($subscriber->fresh()->notifications()->count())->toBe(1);
});

test('the notify job skips a subscriber outside the zone', function () {
    $reporter = User::factory()->create();
    $subscriber = User::factory()->create();

    OutageSubscription::createWithCoordinates(
        ['user_id' => $subscriber->id, 'radius_km' => 5], 10.0, 10.0
    );

    (new NotifySubscribersOfNewOutage(1, 40.4168, -3.7038, 2, $reporter->id))->handle();

    expect($subscriber->fresh()->notifications()->count())->toBe(0);
});

test('the notify job notifies a subscriber when the report\'s own radius reaches a nearby small subscription', function () {
    $reporter = User::factory()->create();
    $subscriber = User::factory()->create();

    // ~12km north of the report's center: outside the subscriber's 5km radius alone,
    // but within reach once the report's own 15km radius is added to it.
    OutageSubscription::createWithCoordinates(
        ['user_id' => $subscriber->id, 'radius_km' => 5], 40.5518, -3.7038
    );

    (new NotifySubscribersOfNewOutage(1, 40.4168, -3.7038, 15, $reporter->id))->handle();

    expect($subscriber->fresh()->notifications()->count())->toBe(1);
});

test('the notify job never notifies the reporter about their own report', function () {
    $reporter = User::factory()->create();

    OutageSubscription::createWithCoordinates(
        ['user_id' => $reporter->id, 'radius_km' => 20], 40.4168, -3.7038
    );

    (new NotifySubscribersOfNewOutage(1, 40.4168, -3.7038, 2, $reporter->id))->handle();

    expect($reporter->fresh()->notifications()->count())->toBe(0);
});

test('creating a report dispatches the subscriber notification job', function () {
    Queue::fake();

    $reporter = User::factory()->create();

    $this->actingAs($reporter)->postJson('/api/v1/reports', [
        'latitude' => 40.4168,
        'longitude' => -3.7038,
        'radius_km' => 2,
        'duration_type' => 'fixed',
        'duration_hours' => 4,
    ])->assertCreated();

    Queue::assertPushed(NotifySubscribersOfNewOutage::class);
});
