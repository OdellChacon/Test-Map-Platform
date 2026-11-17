<?php

namespace Database\Factories;

use App\Enums\ReportDurationType;
use App\Enums\ReportStatus;
use App\Models\OutageReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OutageReportFactory extends Factory
{
    protected $model = OutageReport::class;

    public function definition(): array
    {
        $durationType = fake()->randomElement(ReportDurationType::cases());
        $durationHours = $durationType === ReportDurationType::Fixed
            ? fake()->numberBetween(1, 72)
            : null;
        $expiresAt = now()->addHours($durationType === ReportDurationType::Fixed ? $durationHours : 72);

        return [
            'user_id' => User::factory(),
            'radius_km' => fake()->randomFloat(2, 0.5, 25),
            'duration_type' => $durationType,
            'duration_hours' => $durationHours,
            'expires_at' => $expiresAt,
            'status' => ReportStatus::Active,
            'description' => fake()->optional()->sentence(),
            'address' => fake()->optional(0.8)->address(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (OutageReport $report) {
            [$latitude, $longitude] = self::randomPointNear(40.4168, -3.7038, 15);
            $report->setCoordinates($latitude, $longitude);
        });
    }

    /**
     * A uniformly-distributed random point within $maxRadiusKm of ($lat, $lng).
     * Defaults the map's seed data to Madrid, where the frontend's map centers by default.
     */
    public static function randomPointNear(float $lat, float $lng, float $maxRadiusKm): array
    {
        $earthRadiusKm = 6371;
        $distanceKm = sqrt(fake()->randomFloat(4, 0, 1)) * $maxRadiusKm;
        $bearing = deg2rad(fake()->randomFloat(2, 0, 360));
        $angularDistance = $distanceKm / $earthRadiusKm;

        $latRad = deg2rad($lat);
        $lngRad = deg2rad($lng);

        $newLatRad = asin(
            sin($latRad) * cos($angularDistance) + cos($latRad) * sin($angularDistance) * cos($bearing)
        );
        $newLngRad = $lngRad + atan2(
            sin($bearing) * sin($angularDistance) * cos($latRad),
            cos($angularDistance) - sin($latRad) * sin($newLatRad)
        );

        return [rad2deg($newLatRad), rad2deg($newLngRad)];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReportStatus::Expired,
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
        ]);
    }
}
