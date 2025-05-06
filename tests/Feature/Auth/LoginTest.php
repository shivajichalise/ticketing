<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

test('user can login with valid credentials', function (): void {
    User::factory()->create([
        'name' => 'Pest user',
        'email' => 'pestuser1@gmail.com',
        'password' => bcrypt('password'),
    ]);

    postJson(route('login'), [
        'email' => 'pestuser1@gmail.com',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonStructure([
            'status',
            'message',
            'data' => [
                'access_token',
                'refresh_token',
                'user' => ['id', 'name', 'email'],
            ],
        ]);
});

test('user cant login with invalid credentials', function (): void {
    User::factory()->create([
        'email' => 'pestuser2@gmail.com',
        'password' => bcrypt('password'),
    ]);

    postJson(route('login'), [
        'email' => 'pestuser2@gmail.com',
        'password' => 'wrongpassword',
    ])
        ->assertStatus(401)
        ->assertJson([
            'status' => false,
        ]);
});

test('get refresh token')->todo('other tests related to refresh token too');
