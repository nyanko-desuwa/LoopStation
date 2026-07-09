<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('tạo được tài khoản user qua API', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Nguyen Van A',
        'email' => 'a@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'a@example.com')
        ->assertJsonPath('user.role', 'user');

    $user = User::where('email', 'a@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->role)->toBe('user');
    expect($user->email_verified_at)->toBeNull();
});

it('chặn email trùng', function () {
    User::factory()->create(['email' => 'dup@example.com']);

    $this->postJson('/api/auth/register', [
        'name' => 'B',
        'email' => 'dup@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertStatus(422);
});
