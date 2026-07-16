<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Wallet\AdjustPointsRequest;
use App\Http\Resources\UserWalletResource;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * GET /api/wallet - ví của user đang đăng nhập.
     */
    public function me(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getWallet($request->user());

        return response()->json([
            'wallet' => new UserWalletResource($wallet),
        ]);
    }

    /**
     * GET /api/wallet/history - lịch sử earned+spent của mình.
     */
    public function myHistory(Request $request): JsonResponse
    {
        $result = $this->walletService->history(
            $request->user(),
            $request->integer('per_page', 20)
        );

        return response()->json($result);
    }

    /**
     * GET /api/wallets - manager xem danh sách ví.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        if (! $request->user()?->hasPermission('wallet.view')) {
            abort(403);
        }

        $wallets = $this->walletService->listWallets([
            'user_id' => $request->query('user_id'),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return UserWalletResource::collection($wallets);
    }

    /**
     * GET /api/wallets/{user} - manager xem ví 1 user.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $isOwn = $actor && $actor->id === $user->id;

        if (! $isOwn && ! $actor?->hasPermission('wallet.view')) {
            abort(403);
        }

        $wallet = $this->walletService->getWallet($user);

        return response()->json([
            'wallet' => new UserWalletResource($wallet),
        ]);
    }

    /**
     * GET /api/wallets/{user}/history
     */
    public function history(Request $request, User $user): JsonResponse
    {
        $actor = $request->user();
        $isOwn = $actor && $actor->id === $user->id;

        if (! $isOwn && ! $actor?->hasAnyPermission(['wallet.view', 'points.view_own_history'])) {
            abort(403);
        }

        if ($isOwn && ! $actor->hasPermission('points.view_own_history') && ! $actor->hasPermission('wallet.view_own')) {
            // Own history allowed with wallet.view_own or points.view_own_history.
        }

        $result = $this->walletService->history($user, $request->integer('per_page', 20));

        return response()->json($result);
    }

    /**
     * POST /api/points/adjust - manager cộng/trừ điểm thủ công.
     */
    public function adjust(AdjustPointsRequest $request): JsonResponse
    {
        $target = User::query()->findOrFail($request->validated('user_id'));
        $row = $this->walletService->adjust(
            $target,
            (int) $request->validated('points'),
            $request->validated('direction'),
            $request->validated('description'),
            $request->user()
        );

        $wallet = $this->walletService->getWallet($target)->fresh();

        return response()->json([
            'message' => __('wallets.messages.adjusted'),
            'wallet' => new UserWalletResource($wallet),
            'entry' => [
                'id' => $row->id,
                'type' => $request->validated('direction') === 'credit' ? 'earned' : 'spent',
                'points' => $row->points,
                'source_type' => $row->source_type,
                'description' => $row->description,
                'created_at' => $row->created_at,
            ],
        ]);
    }
}
