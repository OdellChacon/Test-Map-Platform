<?php

use App\Events\PublicOutageReportChanged;
use App\Jobs\ResolveOutageReportAddress;
use App\Models\OutageReport;
use App\Services\NominatimReverseGeocoder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

test('it stores the resolved address and broadcasts the update', function () {
    Event::fake([PublicOutageReportChanged::class]);
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response(['display_name' => 'Puerta del Sol, Madrid, Spain']),
    ]);

    $report = OutageReport::factory()->create();
    $report->setCoordinates(40.4168, -3.7038);

    (new ResolveOutageReportAddress($report->id, 40.4168, -3.7038))
        ->handle(app(NominatimReverseGeocoder::class));

    expect($report->refresh()->address)->toBe('Puerta del Sol, Madrid, Spain');

    Event::assertDispatched(
        PublicOutageReportChanged::class,
        fn ($event) => $event->action === 'updated' && $event->report['address'] === 'Puerta del Sol, Madrid, Spain',
    );
});

test('it leaves the address empty when the geocoding service fails', function () {
    Event::fake([PublicOutageReportChanged::class]);
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response(null, 503),
    ]);

    $report = OutageReport::factory()->create(['address' => null]);
    $report->setCoordinates(40.4168, -3.7038);

    (new ResolveOutageReportAddress($report->id, 40.4168, -3.7038))
        ->handle(app(NominatimReverseGeocoder::class));

    expect($report->refresh()->address)->toBeNull();
    Event::assertNotDispatched(PublicOutageReportChanged::class);
});
