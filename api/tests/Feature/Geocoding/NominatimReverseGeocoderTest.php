<?php

use App\Services\NominatimReverseGeocoder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

test('it resolves a display name from a successful response', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response(['display_name' => 'Puerta del Sol, Madrid, Spain']),
    ]);

    $address = (new NominatimReverseGeocoder)->resolve(40.4168, -3.7038);

    expect($address)->toBe('Puerta del Sol, Madrid, Spain');
    Http::assertSent(fn ($request) => $request->hasHeader('User-Agent', config('services.nominatim.user_agent')));
});

test('it returns null when the service responds with an error', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response(null, 503),
    ]);

    expect((new NominatimReverseGeocoder)->resolve(40.4168, -3.7038))->toBeNull();
});

test('it returns null instead of throwing when the request fails', function () {
    Http::fake(function () {
        throw new ConnectionException('timed out');
    });

    expect((new NominatimReverseGeocoder)->resolve(40.4168, -3.7038))->toBeNull();
});
