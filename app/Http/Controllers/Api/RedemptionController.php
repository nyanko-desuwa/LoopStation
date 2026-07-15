<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Redemption\StoreRedemptionRequest;
use App\Http\Resources\RedemptionResource;
use App\Models\Redemption;
use App\Services\RedemptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RedemptionController extends Controller
{
    public function __construct(private readonly RedemptionService $redemptionService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $items = $this->redemptionService->list($request->user(), [
            'status' => $request->query('status'),
            'per_page' => $request->integer('per_page', 20),
        ]);

        return RedemptionResource::collection($items);
    }

    public function show(Request $request, Redemption $redemption): RedemptionResource
    {
        $redemption = $this->redemptionService->findVisible($redemption, $request->user());

        return new RedemptionResource($redemption);
    }

    public function store(StoreRedemptionRequest $request): JsonResponse
    {
        $redemption = $this->redemptionService->create($request->user(), $request->validated());

        return response()->json([
            'message' => __('redemptions.messages.created'),
            'redemption' => new RedemptionResource($redemption),
        ], 201);
    }

    public function cancel(Request $request, Redemption $redemption): JsonResponse
    {
        $redemption = $this->redemptionService->cancel($redemption, $request->user());

        return response()->json([
            'message' => __('redemptions.messages.cancelled'),
            'redemption' => new RedemptionResource($redemption),
        ]);
    }

    public function markShipping(Request $request, Redemption $redemption): JsonResponse
    {
        if (! $request->user()?->hasPermission('redemption.fulfill')) {
            abort(403);
        }

        $redemption = $this->redemptionService->markShipping($redemption, $request->user());

        return response()->json([
            'message' => __('redemptions.messages.shipping'),
            'redemption' => new RedemptionResource($redemption),
        ]);
    }

    public function fulfill(Request $request, Redemption $redemption): JsonResponse
    {
        if (! $request->user()?->hasPermission('redemption.fulfill')) {
            abort(403);
        }

        $redemption = $this->redemptionService->fulfill($redemption, $request->user());

        return response()->json([
            'message' => __('redemptions.messages.fulfilled'),
            'redemption' => new RedemptionResource($redemption),
        ]);
    }
}
