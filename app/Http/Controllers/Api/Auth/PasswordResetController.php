<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function sendLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendResetLink($request->string('email')->toString());

        return response()->json(['message' => __($status)]);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        return response()->json(['message' => __($status)]);
    }
}
