<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sticker\StoreStickerRequest;
use App\Http\Requests\Sticker\UpdateStickerRequest;
use App\Http\Resources\StickerObtainLogResource;
use App\Http\Resources\StickerResource;
use App\Http\Resources\UserStickerResource;
use App\Models\Sticker;
use App\Models\User;
use App\Services\StickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StickerController extends Controller
{
    public function __construct(private readonly StickerService $stickerService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker.create',
            'sticker.update',
            'sticker.delete',
        ]) ?? false;

        $stickers = $this->stickerService->listStickers([
            'set_id' => $request->query('set_id'),
            'status' => $request->query('status'),
            'rarity' => $request->query('rarity'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 20),
        ], $asManager);

        return StickerResource::collection($stickers);
    }

    public function show(Request $request, Sticker $sticker): StickerResource
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker.update',
            'sticker.delete',
        ]) ?? false;

        $sticker = $this->stickerService->findStickerVisible($sticker, $asManager);

        return new StickerResource($sticker);
    }

    public function store(StoreStickerRequest $request): JsonResponse
    {
        $sticker = $this->stickerService->createSticker($request->validated());

        return response()->json([
            'message' => __('stickers.messages.sticker_created'),
            'sticker' => new StickerResource($sticker),
        ], 201);
    }

    public function update(UpdateStickerRequest $request, Sticker $sticker): JsonResponse
    {
        $sticker = $this->stickerService->updateSticker($sticker, $request->validated());

        return response()->json([
            'message' => __('stickers.messages.sticker_updated'),
            'sticker' => new StickerResource($sticker),
        ]);
    }

    public function destroy(Request $request, Sticker $sticker): JsonResponse
    {
        if (! $request->user()?->hasPermission('sticker.delete')) {
            abort(403);
        }

        $this->stickerService->deleteSticker($sticker);

        return response()->json([
            'message' => __('stickers.messages.sticker_deleted'),
        ]);
    }

    /** Inventory sticker của chính mình. */
    public function myInventory(Request $request): AnonymousResourceCollection
    {
        $items = $this->stickerService->listUserInventory($request->user(), [
            'per_page' => $request->integer('per_page', 50),
        ]);

        return UserStickerResource::collection($items);
    }

    /** Lịch sử nhận sticker của chính mình. */
    public function myObtainLogs(Request $request): AnonymousResourceCollection
    {
        $logs = $this->stickerService->listObtainLogs($request->user(), [
            'per_page' => $request->integer('per_page', 20),
        ]);

        return StickerObtainLogResource::collection($logs);
    }

    /** Staff/manager xem inventory user khác (sticker.view). */
    public function userInventory(Request $request, User $user): AnonymousResourceCollection
    {
        if (
            $request->user()?->id !== $user->id
            && ! $request->user()?->hasPermission('sticker.view')
        ) {
            abort(403);
        }

        $items = $this->stickerService->listUserInventory($user, [
            'per_page' => $request->integer('per_page', 50),
        ]);

        return UserStickerResource::collection($items);
    }
}
