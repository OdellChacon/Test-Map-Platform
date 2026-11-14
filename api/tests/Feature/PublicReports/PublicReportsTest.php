<?php

use App\Models\OutageReport;

function madridBoundingBox(array $overrides = []): array
{
    return array_merge([
        'south_west_lat' => 39,
        'south_west_lng' => -5,
        'north_east_lat' => 42,
        'north_east_lng' => -2,
    ], $overrides);
}

test('the public endpoint requires no authentication', function () {
    $this->getJson('/api/v1/public/reports?'.http_build_query(madridBoundingBox()))->assertOk();
});

test('it only returns active reports inside the bounding box', function () {
    $insideMadrid = OutageReport::factory()->create();
    $insideMadrid->setCoordinates(40.4168, -3.7038);

    $outsideBoundingBox = OutageReport::factory()->create();
    $outsideBoundingBox->setCoordinates(51.5074, -0.1278);

    $closedInsideMadrid = OutageReport::factory()->closed()->create();
    $closedInsideMadrid->setCoordinates(40.42, -3.70);

    $expiredInsideMadrid = OutageReport::factory()->expired()->create();
    $expiredInsideMadrid->setCoordinates(40.41, -3.71);

    $response = $this->getJson('/api/v1/public/reports?'.http_build_query(madridBoundingBox()))
        ->assertOk();

    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.id'))->toBe($insideMadrid->id);
});

test('it excludes reports that are active but past their query-time expiry', function () {
    $staleActive = OutageReport::factory()->create(['expires_at' => now()->subMinute()]);
    $staleActive->setCoordinates(40.4168, -3.7038);

    $response = $this->getJson('/api/v1/public/reports?'.http_build_query(madridBoundingBox()))
        ->assertOk();

    expect($response->json('data'))->toBeEmpty();
});

test('the public resource exposes no personal data', function () {
    $report = OutageReport::factory()->create(['description' => 'Sensitive details about my house']);
    $report->setCoordinates(40.4168, -3.7038);

    $response = $this->getJson('/api/v1/public/reports?'.http_build_query(madridBoundingBox()))
        ->assertOk();

    expect($response->json('data.0'))->toHaveKeys([
        'id', 'latitude', 'longitude', 'radius_km', 'address', 'confirmations_count', 'expires_at',
    ])->and($response->json('data.0'))->not->toHaveKeys(['user_id', 'description', 'user']);
});

test('it validates the bounding box parameters', function () {
    $this->getJson('/api/v1/public/reports')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['south_west_lat', 'south_west_lng', 'north_east_lat', 'north_east_lng']);
});

test('it rejects a bounding box that is too large', function () {
    $this->getJson('/api/v1/public/reports?'.http_build_query([
        'south_west_lat' => -80,
        'south_west_lng' => -170,
        'north_east_lat' => 80,
        'north_east_lng' => 170,
    ]))->assertUnprocessable()->assertJsonValidationErrors('bounding_box');
});
