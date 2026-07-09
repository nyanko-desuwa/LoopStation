<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class LoginController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function __invoke(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->attemptLogin(
            $request->string('login')->toString(),
            $request->string('password')->toString(),
            $request->boolean('remember')
        );

        $token = $user->createToken('api')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công.',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }
}
