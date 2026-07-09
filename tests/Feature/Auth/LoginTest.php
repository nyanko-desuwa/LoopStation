<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('đăng nhập đúng thì nhận token', function () {
    User::factory()->create([
        'email' => 'a@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/auth/login', [
        'login' => 'a@example.com',
        'password' => 'password123',
    ])->assertOk()->assertJsonStructure(['token', 'user']);
});

it('sai mật khẩu thì báo lỗi', function () {
    User::factory()->create([
        'email' => 'a@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/auth/login', [
        'login' => 'a@example.com',
        'password' => 'wrong-pass',
    ])->assertStatus(422);
});

it('tài khoản bị khóa thì không vào được', function () {
    User::factory()->locked()->create([
        'email' => 'locked@example.com',
        'password' => Hash::make('password123'),
    ]);

    $this->postJson('/api/auth/login', [
        'login' => 'locked@example.com',
        'password' => 'password123',
    ])->assertStatus(422);
});

it('me cần token', function () {
    $this->getJson('/api/auth/me')->assertUnauthorized();
});

it('me trả về user khi có token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('user.id', $user->id);
});
