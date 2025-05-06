<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Cache::flush();

    $this->user = User::factory()->create([
        'email' => 'locked@example.com',
        'password' => bcrypt('password123'),
    ]);
});

test('user is locked after exceeding max login attempts', function (): void {
    $email = $this->user->email;
    $ip = '127.0.0.1';

    foreach ([1, 2, 3] as $i) {
        $response = postJson(route('login'), [
            'email' => $email,
            'password' => 'wrong-password',
        ], ['REMOTE_ADDR' => $ip]);

        $response->assertStatus(401)
            ->assertJsonFragment(['status' => false]);
    }

    $response = postJson(route('login'), [
        'email' => $email,
        'password' => 'wrong-password',
    ], ['REMOTE_ADDR' => $ip]);

    $response->assertStatus(429)
        ->assertJson([
            'status' => false,
        ]);
});

test('backoff delay increases with each failed login beyond free attempts')->todo();
