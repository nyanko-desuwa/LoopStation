<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reward\StoreRewardCatalogRequest;
use App\Http\Requests\Reward\UpdateRewardCatalogRequest;
use App\Http\Resources\RewardCatalogResource;
use App\Models\RewardCatalog;
use App\Services\RewardCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RewardCatalogController extends Controller
{
    public function __construct(private readonly RewardCatalogService $rewardCatalogService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $asManager = $request->user()?->hasAnyPermission([
            'reward_catalog.update',
            'reward_catalog.delete',
            'reward_catalog.create',
        ]) ?? false;

        $rewards = $this->rewardCatalogService->list([
            'status' => $request->query('status'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 20),
        ], $asManager);

        return RewardCatalogResource::collection($rewards);
    }

    public function show(Request $request, RewardCatalog $rewardCatalog): RewardCatalogResource
    {
        $asManager = $request->user()?->hasAnyPermission([
            'reward_catalog.update',
            'reward_catalog.delete',
        ]) ?? false;

        if (! $asManager && ! $rewardCatalog->isActive()) {
            abort(404);
        }

        return new RewardCatalogResource($rewardCatalog);
    }

    public function store(StoreRewardCatalogRequest $request): JsonResponse
    {
        $reward = $this->rewardCatalogService->create($request->validated());

        return response()->json([
            'message' => __('redemptions.messages.reward_created'),
            'reward' => new RewardCatalogResource($reward),
        ], 201);
    }

    public function update(UpdateRewardCatalogRequest $request, RewardCatalog $rewardCatalog): JsonResponse
    {
        $reward = $this->rewardCatalogService->update($rewardCatalog, $request->validated());

        return response()->json([
            'message' => __('redemptions.messages.reward_updated'),
            'reward' => new RewardCatalogResource($reward),
        ]);
    }

    public function destroy(Request $request, RewardCatalog $rewardCatalog): JsonResponse
    {
        if (! $request->user()?->hasPermission('reward_catalog.delete')) {
            abort(403);
        }

        $this->rewardCatalogService->delete($rewardCatalog);

        return response()->json([
            'message' => __('redemptions.messages.reward_deleted'),
        ]);
    }
}
