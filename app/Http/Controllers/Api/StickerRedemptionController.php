<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StickerReward\StoreStickerRedemptionRequest;
use App\Http\Resources\StickerRedemptionResource;
use App\Models\StickerRedemption;
use App\Services\StickerRedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StickerRedemptionController extends Controller
{
    public function __construct(private readonly StickerRedemptionService $redemptionService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->redemptionService->list($request->user(), [
            'status' => $request->query('status'),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return StickerRedemptionResource::collection($items);
    }

    public function show(Request $request, StickerRedemption $stickerRedemption): StickerRedemptionResource
    {
        $redemption = $this->redemptionService->findVisible($stickerRedemption, $request->user());

        return new StickerRedemptionResource($redemption);
    }

    public function store(StoreStickerRedemptionRequest $request): JsonResponse
    {
        $redemption = $this->redemptionService->create($request->user(), $request->validated());

        return response()->json([
            'message' => __('sticker_rewards.messages.redeemed'),
            'redemption' => new StickerRedemptionResource($redemption),
        ], 201);
    }

    public function cancel(Request $request, StickerRedemption $stickerRedemption): JsonResponse
    {
        $redemption = $this->redemptionService->cancel($stickerRedemption, $request->user());

        return response()->json([
            'message' => __('sticker_rewards.messages.cancelled'),
            'redemption' => new StickerRedemptionResource($redemption),
        ]);
    }

    public function markShipping(Request $request, StickerRedemption $stickerRedemption): JsonResponse
    {
        if (! $request->user()?->hasPermission('sticker_redemption.fulfill')) {
            abort(403);
        }

        $redemption = $this->redemptionService->markShipping($stickerRedemption, $request->user());

        return response()->json([
            'message' => __('sticker_rewards.messages.shipping'),
            'redemption' => new StickerRedemptionResource($redemption),
        ]);
    }

    public function fulfill(Request $request, StickerRedemption $stickerRedemption): JsonResponse
    {
        if (! $request->user()?->hasPermission('sticker_redemption.fulfill')) {
            abort(403);
        }

        $redemption = $this->redemptionService->fulfill($stickerRedemption, $request->user());

        return response()->json([
            'message' => __('sticker_rewards.messages.fulfilled'),
            'redemption' => new StickerRedemptionResource($redemption),
        ]);
    }
}
