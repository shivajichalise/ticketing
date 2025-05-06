<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('new user can register', function (): void {
    $response = postJson(route('register'), [
        'name' => 'Raju Poudel',
        'email' => 'raju@gmail.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertStatus(201)
        ->assertJsonStructure([
            'status',
            'data',
            'message',
        ])
        ->assertJson([
            'status' => true,
            'message' => 'Registration successful',
            'data' => [],
        ]);
});

test('user cant register with invalid inputs', function (): void {
    $response = postJson(route('register'), [
        'name' => '',
        'email' => 'rajua.com',
        'password' => 'short',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'email', 'password']);
});
