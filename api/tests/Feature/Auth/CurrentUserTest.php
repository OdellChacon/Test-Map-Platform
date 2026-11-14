<?php

use App\Models\User;

test('a guest cannot fetch the current user', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

test('an authenticated user can fetch their own data', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/me')
        ->assertOk()
        ->assertJson(['data' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ]]);
});
