<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StickerReward\AdjustStockRequest;
use App\Http\Requests\StickerReward\StoreStickerRewardItemRequest;
use App\Http\Requests\StickerReward\UpdateStickerRewardItemRequest;
use App\Http\Resources\StickerRewardItemResource;
use App\Models\StickerRewardItem;
use App\Services\StickerRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StickerRewardItemController extends Controller
{
    public function __construct(private readonly StickerRewardService $rewardService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker_reward_item.create',
            'sticker_reward_item.update',
            'sticker_reward_item.delete',
        ]) ?? false;

        $items = $this->rewardService->listItems([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 20),
        ], $asManager);

        return StickerRewardItemResource::collection($items);
    }

    public function show(Request $request, StickerRewardItem $stickerRewardItem): StickerRewardItemResource
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker_reward_item.update',
            'sticker_reward_item.delete',
        ]) ?? false;

        $item = $this->rewardService->findItemVisible($stickerRewardItem, $asManager);

        return new StickerRewardItemResource($item);
    }

    public function store(StoreStickerRewardItemRequest $request): JsonResponse
    {
        $item = $this->rewardService->createItem($request->validated());

        return response()->json([
            'message' => __('sticker_rewards.messages.item_created'),
            'reward_item' => new StickerRewardItemResource($item),
        ], 201);
    }

    public function update(UpdateStickerRewardItemRequest $request, StickerRewardItem $stickerRewardItem): JsonResponse
    {
        $item = $this->rewardService->updateItem($stickerRewardItem, $request->validated());

        return response()->json([
            'message' => __('sticker_rewards.messages.item_updated'),
            'reward_item' => new StickerRewardItemResource($item),
        ]);
    }

    public function destroy(Request $request, StickerRewardItem $stickerRewardItem): JsonResponse
    {
        if (! $request->user()?->hasPermission('sticker_reward_item.delete')) {
            abort(403);
        }

        $this->rewardService->deleteItem($stickerRewardItem);

        return response()->json([
            'message' => __('sticker_rewards.messages.item_deleted'),
        ]);
    }

    public function adjustStock(AdjustStockRequest $request, StickerRewardItem $stickerRewardItem): JsonResponse
    {
        $item = $this->rewardService->adjustStock($stickerRewardItem, (int) $request->validated('stock'));

        return response()->json([
            'message' => __('sticker_rewards.messages.stock_adjusted'),
            'reward_item' => new StickerRewardItemResource($item),
        ]);
    }
}
