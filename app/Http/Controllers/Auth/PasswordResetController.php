<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(ForgotPasswordRequest $request): RedirectResponse
    {
        $status = $this->authService->sendResetLink($request->string('email')->toString());

        return back()->with('status', __($status));
    }

    public function resetForm(string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => request('email')]);
    }

    public function reset(ResetPasswordRequest $request): RedirectResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', __($status))
            : back()->with('status', __($status));
    }
}
