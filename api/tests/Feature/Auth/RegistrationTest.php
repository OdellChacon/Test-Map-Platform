<?php

use App\Models\User;

test('a user can register with valid data', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonMissing(['password']);

    $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
});

test('registration requires a matching password confirmation', function () {
    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'does-not-match',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('password');
    $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
});

test('registration rejects an email already in use', function () {
    User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertUnprocessable()->assertJsonValidationErrors('email');
});

test('a registered user is immediately authenticated', function () {
    $this->postJson('/api/v1/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->getJson('/api/v1/me')->assertSuccessful()->assertJsonPath('data.email', 'jane@example.com');
});
