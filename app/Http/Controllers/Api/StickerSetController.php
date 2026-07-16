<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Sticker\StoreStickerSetRequest;
use App\Http\Requests\Sticker\UpdateStickerSetRequest;
use App\Http\Resources\StickerSetResource;
use App\Models\StickerSet;
use App\Services\StickerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StickerSetController extends Controller
{
    public function __construct(private readonly StickerService $stickerService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker_set.create',
            'sticker_set.update',
            'sticker_set.delete',
        ]) ?? false;

        $sets = $this->stickerService->listSets([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 20),
        ], $asManager);

        return StickerSetResource::collection($sets);
    }

    public function show(Request $request, StickerSet $stickerSet): StickerSetResource
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker_set.update',
            'sticker_set.delete',
            'sticker.create',
        ]) ?? false;

        $set = $this->stickerService->findSetVisible($stickerSet, $asManager);

        return new StickerSetResource($set);
    }

    public function store(StoreStickerSetRequest $request): JsonResponse
    {
        $set = $this->stickerService->createSet($request->validated());

        return response()->json([
            'message' => __('stickers.messages.set_created'),
            'sticker_set' => new StickerSetResource($set),
        ], 201);
    }

    public function update(UpdateStickerSetRequest $request, StickerSet $stickerSet): JsonResponse
    {
        $set = $this->stickerService->updateSet($stickerSet, $request->validated());

        return response()->json([
            'message' => __('stickers.messages.set_updated'),
            'sticker_set' => new StickerSetResource($set),
        ]);
    }

    public function destroy(Request $request, StickerSet $stickerSet): JsonResponse
    {
        if (! $request->user()?->hasPermission('sticker_set.delete')) {
            abort(403);
        }

        $this->stickerService->deleteSet($stickerSet);

        return response()->json([
            'message' => __('stickers.messages.set_deleted'),
        ]);
    }
}
