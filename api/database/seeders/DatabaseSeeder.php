<?php

namespace Database\Seeders;

use App\Models\OutageReport;
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

        OutageReport::factory()->count(3)->for($demoUser)->create();

        User::factory()->count(10)->create()->each(function (User $user) {
            OutageReport::factory()
                ->count(fake()->numberBetween(1, 3))
                ->for($user)
                ->create();
        });
    }
}
