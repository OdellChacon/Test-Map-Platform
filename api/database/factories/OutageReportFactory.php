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
            $report->setCoordinates(fake()->latitude(-60, 70), fake()->longitude(-170, 170));
        });
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
            'expires_at' => now()->subHour(),
        ]);
    }
}
