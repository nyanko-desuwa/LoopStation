<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user() ?? \App\Models\User::findOrFail($request->route('id'));

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email đã xác minh rồi.']);
        }

        if (! hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            abort(403);
        }

        $this->authService->verifyEmail($user);

        return response()->json(['message' => 'Xác minh email xong.']);
    }

    public function send(Request $request): JsonResponse
    {
        $this->authService->sendVerification($request->user());

        return response()->json(['message' => 'Đã gửi lại email xác minh.']);
    }
}
