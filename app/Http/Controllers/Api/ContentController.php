<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Content\StoreContentRequest;
use App\Http\Requests\Content\UpdateContentRequest;
use App\Http\Resources\ContentReadResource;
use App\Http\Resources\EducationalContentResource;
use App\Http\Resources\StickerResource;
use App\Models\ContentRead;
use App\Models\EducationalContent;
use App\Services\ContentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentController extends Controller
{
    public function __construct(private readonly ContentService $contentService)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $contents = $this->contentService->list($request->user(), [
            'status' => $request->query('status'),
            'author_id' => $request->query('author_id'),
            'search' => $request->query('search'),
            'per_page' => $request->integer('per_page', 15),
        ]);

        return EducationalContentResource::collection($contents);
    }

    public function show(Request $request, EducationalContent $content): EducationalContentResource
    {
        $content = $this->contentService->findVisible($content, $request->user());

        return new EducationalContentResource($content);
    }

    public function store(StoreContentRequest $request): JsonResponse
    {
        $content = $this->contentService->create($request->user(), $request->validated());

        return response()->json([
            'message' => __('contents.messages.created'),
            'content' => new EducationalContentResource($content),
        ], 201);
    }

    public function update(UpdateContentRequest $request, EducationalContent $content): JsonResponse
    {
        $content = $this->contentService->update($content, $request->user(), $request->validated());

        return response()->json([
            'message' => __('contents.messages.updated'),
            'content' => new EducationalContentResource($content),
        ]);
    }

    public function approve(Request $request, EducationalContent $content): JsonResponse
    {
        if (! $request->user()?->hasPermission('content.approve')) {
            abort(403);
        }

        $content = $this->contentService->approve($content, $request->user());

        return response()->json([
            'message' => __('contents.messages.approved'),
            'content' => new EducationalContentResource($content),
        ]);
    }

    public function reject(Request $request, EducationalContent $content): JsonResponse
    {
        if (! $request->user()?->hasPermission('content.approve')) {
            abort(403);
        }

        $content = $this->contentService->reject($content, $request->user());

        return response()->json([
            'message' => __('contents.messages.rejected'),
            'content' => new EducationalContentResource($content),
        ]);
    }

    public function destroy(Request $request, EducationalContent $content): JsonResponse
    {
        if (! $request->user()?->hasPermission('content.delete')) {
            abort(403);
        }

        $this->contentService->delete($content);

        return response()->json([
            'message' => __('contents.messages.deleted'),
        ]);
    }

    /**
     * User mở đọc bài → tạo dòng đọc (started_at) để frontend đếm ngược timer.
     */
    public function startRead(Request $request, EducationalContent $content): JsonResponse
    {
        $read = $this->contentService->startRead($content, $request->user());

        return response()->json([
            'message' => __('contents.messages.read_started'),
            'read' => new ContentReadResource($read),
        ], 201);
    }

    /**
     * User hoàn tất đọc → kiểm tra timer + quota, cộng điểm nếu đạt.
     */
    public function completeRead(Request $request, EducationalContent $content, ContentRead $read): JsonResponse
    {
        // Dòng đọc phải thuộc đúng bài trên URL.
        if ((int) $read->content_id !== (int) $content->id) {
            abort(404);
        }

        $result = $this->contentService->completeRead($read, $request->user());

        $drop = $result['sticker_drop'] ?? null;
        $stickerPayload = null;
        if (is_array($drop) && ($drop['sticker'] ?? null) !== null) {
            $stickerPayload = [
                'sticker' => new StickerResource($drop['sticker']),
                'first_owned' => (bool) ($drop['first_owned'] ?? false),
                'bonus_points' => (int) ($drop['bonus_points'] ?? 0),
                'unlocked_content_id' => $drop['unlocked_content_id'] ?? null,
            ];
        }

        return response()->json([
            'message' => $result['rewarded']
                ? __('contents.messages.read_rewarded')
                : __('contents.messages.read_completed'),
            'read' => new ContentReadResource($result['read']),
            'rewarded' => $result['rewarded'],
            'points_awarded' => $result['points_awarded'],
            'reason' => $result['reason'],
            'sticker_drop' => $stickerPayload,
        ]);
    }
}
