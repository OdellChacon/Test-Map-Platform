<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class NominatimReverseGeocoder
{
    public function resolve(float $latitude, float $longitude): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
            ])->timeout(5)->get(config('services.nominatim.base_url').'/reverse', [
                'format' => 'jsonv2',
                'lat' => $latitude,
                'lon' => $longitude,
                'zoom' => 18,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Reverse geocoding request failed.', ['message' => $exception->getMessage()]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        return $response->json('display_name');
    }
}
