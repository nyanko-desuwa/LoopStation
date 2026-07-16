<?php

namespace App\Services;

use App\Models\User;
use App\Support\EmailBox;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

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

        // Tạo ví điểm xanh 1-1 ngay khi đăng ký (kể cả walk-in sau này).
        app(WalletService::class)->ensureWallet($user);

        event(new Registered($user));

        return $user;
    }

    public function attemptLogin(string $login, string $password, bool $remember = false): User
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email_canonical' : 'phone';
        $value = $field === 'email_canonical' ? EmailBox::normalize($login) : $login;

        $user = User::query()
            ->where($field, $value)
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
        $user = $this->findUserByCanonicalEmail($email);

        return Password::sendResetLink([
            'email' => $user?->email ?? $email,
        ]);
    }

    public function resetPassword(array $data): string
    {
        $user = $this->findUserByCanonicalEmail($data['email']);

        return Password::reset(
            [
                'email' => $user?->email ?? $data['email'],
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

    private function findUserByCanonicalEmail(string $email): ?User
    {
        $canonical = EmailBox::normalize($email);

        if ($canonical === '') {
            return null;
        }

        return User::query()
            ->where('email_canonical', $canonical)
            ->first();
    }
}
