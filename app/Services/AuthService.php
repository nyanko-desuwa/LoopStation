<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthService
{
    public function register(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'user',
            'status' => 'active',
            'is_walk_in' => false,
            'must_change_password' => false,
            'email_verified_at' => null,
        ]);

        event(new Registered($user));

        return $user;
    }

    public function attemptLogin(string $login, string $password, bool $remember = false): User
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $user = User::query()
            ->where($field, $login)
            ->where('status', 'active')
            ->first();

        if (! $user || ! Hash::check($password, $user->password ?? '')) {
            throw ValidationException::withMessages([
                'login' => __('Thông tin đăng nhập chưa đúng.'),
            ]);
        }

        Auth::login($user, $remember);

        return $user;
    }

    public function logout(): void
    {
        Auth::logout();
    }

    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): string
    {
        return Password::reset(
            [
                'email' => $data['email'],
                'password' => $data['password'],
                'password_confirmation' => $data['password'],
                'token' => $data['token'],
            ],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'must_change_password' => false,
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );
    }

    public function sendVerification(User $user): void
    {
        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }
    }

    public function verifyEmail(User $user): void
    {
        if ($user->hasVerifiedEmail()) {
            return;
        }

        $user->markEmailAsVerified();
        event(new Verified($user));
    }
}
