<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StickerReward\StoreStickerRewardRuleRequest;
use App\Http\Requests\StickerReward\UpdateStickerRewardRuleRequest;
use App\Http\Resources\StickerRewardRuleResource;
use App\Models\Sticker;
use App\Models\StickerRewardRule;
use App\Services\StickerRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class StickerRewardRuleController extends Controller
{
    public function __construct(private readonly StickerRewardService $rewardService)
    {
    }

    public function index(Request $request, Sticker $sticker): AnonymousResourceCollection
    {
        $asManager = $request->user()?->hasAnyPermission([
            'sticker_reward_rule.create',
            'sticker_reward_rule.update',
            'sticker_reward_rule.delete',
        ]) ?? false;

        $rules = $this->rewardService->listRules($sticker, [
            'status' => $request->query('status'),
            'per_page' => $request->integer('per_page', 50),
        ], $asManager);

        return StickerRewardRuleResource::collection($rules);
    }

    public function store(StoreStickerRewardRuleRequest $request, Sticker $sticker): JsonResponse
    {
        $rule = $this->rewardService->createRule($sticker, $request->validated());

        return response()->json([
            'message' => __('sticker_rewards.messages.rule_created'),
            'rule' => new StickerRewardRuleResource($rule),
        ], 201);
    }

    public function update(UpdateStickerRewardRuleRequest $request, StickerRewardRule $rule): JsonResponse
    {
        $rule = $this->rewardService->updateRule($rule, $request->validated());

        return response()->json([
            'message' => __('sticker_rewards.messages.rule_updated'),
            'rule' => new StickerRewardRuleResource($rule),
        ]);
    }

    public function destroy(Request $request, StickerRewardRule $rule): JsonResponse
    {
        if (! $request->user()?->hasPermission('sticker_reward_rule.delete')) {
            abort(403);
        }

        $this->rewardService->deleteRule($rule);

        return response()->json([
            'message' => __('sticker_rewards.messages.rule_deleted'),
        ]);
    }
}
