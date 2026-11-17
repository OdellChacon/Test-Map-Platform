<?php

namespace Database\Seeders;

use App\Models\OutageReport;
use App\Models\OutageReportConfirmation;
use App\Models\User;
use Database\Factories\OutageReportFactory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@outagemap.test',
        ]);

        User::factory()->create([
            'name' => 'Demo Moderator',
            'email' => 'moderator@outagemap.test',
            'is_moderator' => true,
        ]);

        // Leaves the demo account below its active-report cap (config('outage.max_active_reports_per_user'))
        // so a reviewer can create a new report live without hitting the limit, and gives it one closed and
        // one expired report so "My reports" shows past history, not only active ones.
        $demoReports = OutageReport::factory()->count(2)->for($demoUser)->create()
            ->push(OutageReport::factory()->closed()->for($demoUser)->create())
            ->push(OutageReport::factory()->expired()->for($demoUser)->create());

        $otherUsers = User::factory()->count(10)->create()->each(function (User $user) {
            OutageReport::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($user)
                ->create();
        });

        // A handful of reports packed within ~500m of each other, so the public map's marker
        // clustering (bonus B5) has something to actually cluster at low zoom levels.
        $clusterSpots = [
            [40.4300, -3.6900],
            [40.4000, -3.7200],
        ];
        foreach ($clusterSpots as [$spotLat, $spotLng]) {
            User::factory()->count(4)->create()->each(function (User $user) use ($spotLat, $spotLng) {
                $report = OutageReport::factory()->for($user)->create();
                [$latitude, $longitude] = OutageReportFactory::randomPointNear($spotLat, $spotLng, 0.5);
                $report->setCoordinates($latitude, $longitude);
            });
        }

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
