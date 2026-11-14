<?php

namespace Database\Seeders;

use App\Models\OutageReport;
use App\Models\OutageReportConfirmation;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@outagemap.test',
        ]);

        $demoReports = OutageReport::factory()->count(3)->for($demoUser)->create();

        $otherUsers = User::factory()->count(10)->create()->each(function (User $user) {
            OutageReport::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($user)
                ->create();
        });

        $demoReports->each(function (OutageReport $report) use ($otherUsers) {
            $otherUsers->random(fake()->numberBetween(0, 5))->unique()->each(function (User $user) use ($report) {
                OutageReportConfirmation::query()->create([
                    'outage_report_id' => $report->id,
                    'user_id' => $user->id,
                ]);
            });
        });
    }
}
