<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('forgot-password ghi token vào bảng', function () {
    Notification::fake();
    $user = User::factory()->create(['email' => 'a@example.com']);

    $this->postJson('/api/auth/forgot-password', [
        'email' => 'a@example.com',
    ])->assertOk();

    $this->assertDatabaseHas('password_reset_tokens', ['email' => 'a@example.com']);
    Notification::assertSentTo($user, ResetPassword::class);
});

it('reset đổi được mật khẩu', function () {
    $user = User::factory()->create([
        'email' => 'a@example.com',
        'password' => Hash::make('old-password'),
    ]);
    $token = Password::createToken($user);

    $this->postJson('/api/auth/reset-password', [
        'token' => $token,
        'email' => 'a@example.com',
        'password' => 'new-password123',
        'password_confirmation' => 'new-password123',
    ])->assertOk();

    expect(Hash::check('new-password123', $user->fresh()->password))->toBeTrue();
});
