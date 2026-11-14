<?php

namespace App\Jobs;

use App\Events\PublicOutageReportChanged;
use App\Models\OutageReport;
use App\Services\NominatimReverseGeocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResolveOutageReportAddress implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $reportId,
        public readonly float $latitude,
        public readonly float $longitude,
    ) {}

    public function handle(NominatimReverseGeocoder $geocoder): void
    {
        $address = $geocoder->resolve($this->latitude, $this->longitude);

        if ($address === null) {
            return;
        }

        $report = OutageReport::query()->whereKey($this->reportId)->first();

        if ($report === null) {
            return;
        }

        $report->update(['address' => $address]);

        PublicOutageReportChanged::dispatch('updated', $report->id, [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'radius_km' => (float) $report->radius_km,
            'expires_at' => $report->expires_at->toIso8601String(),
            'address' => $address,
            'confirmations_count' => $report->confirmations()->count(),
        ]);
    }
}
