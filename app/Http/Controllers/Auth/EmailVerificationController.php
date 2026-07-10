<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function notice(): View
    {
        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->intended('/');
    }

    public function send(Request $request): RedirectResponse
    {
        $this->authService->sendVerification($request->user());

        return back()->with('status', __('auth.verification_link_sent'));
    }
}
