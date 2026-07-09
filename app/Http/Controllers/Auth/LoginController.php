<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $this->authService->attemptLogin(
            $request->string('login')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        $request->session()->regenerate();

        return redirect()->intended('/');
    }

    public function destroy(): RedirectResponse
    {
        $this->authService->logout();

        return redirect()->route('login');
    }
}
