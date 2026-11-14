<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('an authenticated user can log out', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $this->postJson('/api/v1/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertOk();

    $this->getJson('/api/v1/me')->assertOk();

    $this->postJson('/api/v1/logout')->assertNoContent();

    expect(auth()->guard('web')->check())->toBeFalse();
});

test('a guest cannot log out', function () {
    $this->postJson('/api/v1/logout')->assertUnauthorized();
});
