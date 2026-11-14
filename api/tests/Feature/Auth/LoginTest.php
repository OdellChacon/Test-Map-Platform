<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a user can log in with correct credentials', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ]);

    $response->assertOk()->assertJsonPath('data.id', $user->id);
    $this->getJson('/api/v1/me')->assertOk();
});

test('login fails with an incorrect password', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $response = $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

test('login fails for an unknown email', function () {
    $response = $this->postJson('/api/v1/login', [
        'email' => 'nobody@example.com',
        'password' => 'whatever123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('login is rate limited after repeated failures', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $attempts = (int) config('outage.auth_rate_limit.attempts');

    for ($i = 0; $i < $attempts; $i++) {
        $this->postJson('/api/v1/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertStatus(429);
});
