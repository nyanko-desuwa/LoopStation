<?php

namespace App\Services;

use App\Models\ContentRead;
use App\Models\EducationalContent;
use App\Models\PointEarned;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ContentService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly StickerService $stickerService,
    ) {
    }

    /**
     * List bài. Manager/staff (content.view) thấy tất cả; user chỉ thấy published.
     *
     * @param  array{status?: string|null, author_id?: int|null, search?: string|null, per_page?: int}  $filters
     */
    public function list(?User $viewer, array $filters = []): LengthAwarePaginator
    {
        $query = EducationalContent::query()
            ->with(['author', 'approver'])
            ->latest('id');

        $canManage = $viewer?->hasAnyPermission([
            'content.view', 'content.approve', 'content.update', 'content.create',
        ]) ?? false;

        if ($canManage) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
            if (! empty($filters['author_id'])) {
                $query->where('author_id', (int) $filters['author_id']);
            }
        } else {
            // Guest/user chỉ thấy bài đã publish.
            $query->published();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('title', 'like', "%{$search}%");
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function findVisible(EducationalContent $content, ?User $viewer): EducationalContent
    {
        $canManage = $viewer?->hasAnyPermission([
            'content.view', 'content.approve', 'content.update',
        ]) ?? false;

        // Author luôn xem được bài mình soạn dù chưa publish.
        $isAuthor = $viewer !== null && $content->author_id === $viewer->id;

        if (! $content->isPublished() && ! $canManage && ! $isAuthor) {
            abort(404);
        }

        return $content->load(['author', 'approver']);
    }

    /**
     * Staff/manager soạn bài mới (status = pending).
     *
     * @param  array{title: string, content: string, thumbnail_url?: string|null, timer_seconds?: int, points_reward?: int, sticker_set_id?: int|null}  $data
     */
    public function create(User $author, array $data): EducationalContent
    {
        return EducationalContent::create([
            'title' => $data['title'],
            'content' => $data['content'],
            'author_id' => $author->id,
            'thumbnail_url' => $data['thumbnail_url'] ?? null,
            'status' => EducationalContent::STATUS_PENDING,
            'timer_seconds' => $data['timer_seconds'] ?? 120,
            'points_reward' => $data['points_reward'] ?? 0,
            'sticker_set_id' => $data['sticker_set_id'] ?? null,
        ])->load(['author', 'approver', 'stickerSet']);
    }

    /**
     * Cập nhật bài. Chỉ cho sửa khi chưa publish (pending/rejected).
     *
     * @param  array{title?: string, content?: string, thumbnail_url?: string|null, timer_seconds?: int, points_reward?: int, sticker_set_id?: int|null}  $data
     */
    public function update(EducationalContent $content, User $actor, array $data): EducationalContent
    {
        // Author sửa bài của mình, hoặc người có content.update.
        $isAuthor = $content->author_id === $actor->id;
        if (! $isAuthor && ! $actor->hasPermission('content.update')) {
            abort(403);
        }

        if ($content->isPublished()) {
            throw ValidationException::withMessages([
                'content' => __('contents.messages.not_editable'),
            ]);
        }

        $content->fill(collect($data)->only([
            'title', 'content', 'thumbnail_url', 'timer_seconds', 'points_reward', 'sticker_set_id',
        ])->all());
        $content->save();

        return $content->refresh()->load(['author', 'approver', 'stickerSet']);
    }

    /**
     * Manager duyệt bài pending → published.
     */
    public function approve(EducationalContent $content, User $manager): EducationalContent
    {
        if (! $content->isPending()) {
            throw ValidationException::withMessages([
                'content' => __('contents.messages.not_pending'),
            ]);
        }

        $content->update([
            'status' => EducationalContent::STATUS_PUBLISHED,
            'approved_by_id' => $manager->id,
        ]);

        return $content->refresh()->load(['author', 'approver']);
    }

    /**
     * Manager từ chối bài pending → rejected.
     */
    public function reject(EducationalContent $content, User $manager): EducationalContent
    {
        if (! $content->isPending()) {
            throw ValidationException::withMessages([
                'content' => __('contents.messages.not_pending'),
            ]);
        }

        $content->update([
            'status' => EducationalContent::STATUS_REJECTED,
            'approved_by_id' => $manager->id,
        ]);

        return $content->refresh()->load(['author', 'approver']);
    }

    public function delete(EducationalContent $content): void
    {
        $content->delete();
    }

    /**
     * User mở đọc bài published → tạo dòng CONTENT_READS (started_at).
     * Trả về dòng đọc để frontend đếm ngược timer.
     */
    public function startRead(EducationalContent $content, User $user): ContentRead
    {
        if (! $content->isPublished()) {
            abort(404);
        }

        return ContentRead::create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'started_at' => now(),
            'completed_at' => null,
            'rewarded' => false,
            'read_date' => now()->toDateString(),
        ]);
    }

    /**
     * Hoàn tất đọc: kiểm tra timer + quota, nếu đạt thì cộng điểm + drop sticker (1 TX, idempotent theo read).
     *
     * @return array{
     *   read: ContentRead,
     *   rewarded: bool,
     *   points_awarded: int,
     *   reason: string|null,
     *   sticker_drop: array{sticker: \App\Models\Sticker|null, first_owned: bool, bonus_points: int, unlocked_content_id: int|null}|null
     * }
     */
    public function completeRead(ContentRead $read, User $user): array
    {
        if ($read->user_id !== $user->id) {
            abort(403);
        }

        $content = $read->content()->firstOrFail();

        return DB::transaction(function () use ($read, $user, $content): array {
            // Khóa dòng đọc để tránh double-reward khi bấm hoàn tất nhiều lần.
            $locked = ContentRead::query()->whereKey($read->id)->lockForUpdate()->firstOrFail();

            // Đã rewarded trước đó → trả nguyên trạng, không cộng lại / không drop lại.
            if ($locked->rewarded) {
                return [
                    'read' => $locked,
                    'rewarded' => true,
                    'points_awarded' => $content->points_reward,
                    'reason' => null,
                    'sticker_drop' => null,
                ];
            }

            // Timer: thời gian trôi qua từ started_at phải >= timer_seconds của bài.
            $elapsed = now()->diffInSeconds(Carbon::parse($locked->started_at), true);
            if ($elapsed < $content->timer_seconds) {
                throw ValidationException::withMessages([
                    'read' => __('contents.messages.timer_not_reached'),
                ]);
            }

            // Đánh dấu hoàn thành timer (kể cả khi không còn quota vẫn ghi completed_at).
            $locked->completed_at = now();

            $reason = $this->quotaReason($user, $content, $locked);
            $hasPointReward = $content->points_reward > 0;
            $hasStickerSet = $content->sticker_set_id !== null;

            // Hết quota hoặc không có gì để thưởng (điểm / sticker) → chỉ lưu completed_at.
            if ($reason !== null || (! $hasPointReward && ! $hasStickerSet)) {
                $locked->save();

                return [
                    'read' => $locked->refresh(),
                    'rewarded' => false,
                    'points_awarded' => 0,
                    'reason' => $reason ?? 'no_reward',
                    'sticker_drop' => null,
                ];
            }

            $pointsAwarded = 0;
            if ($hasPointReward) {
                $this->walletService->earn($user, $content->points_reward, [
                    'source_type' => PointEarned::SOURCE_CONTENT_READ,
                    'reference_id' => $locked->id,
                    'description' => __('contents.messages.points_earned_description', [
                        'title' => $content->title,
                    ]),
                ]);
                $pointsAwarded = $content->points_reward;
            }

            // Drop sticker từ set gắn bài (nếu có). Cùng TX; only once vì rewarded flag.
            $stickerDrop = $hasStickerSet
                ? $this->stickerService->dropFromContent($user, $content)
                : [
                    'sticker' => null,
                    'first_owned' => false,
                    'bonus_points' => 0,
                    'unlocked_content_id' => null,
                ];

            $stickerAwarded = ($stickerDrop['sticker'] ?? null) !== null;

            // Set locked / pool rỗng + không có điểm → không đốt quota rewarded.
            if ($pointsAwarded <= 0 && ! $stickerAwarded) {
                $locked->save();

                return [
                    'read' => $locked->refresh(),
                    'rewarded' => false,
                    'points_awarded' => 0,
                    'reason' => 'no_reward',
                    'sticker_drop' => null,
                ];
            }

            $locked->rewarded = true;
            $locked->save();

            return [
                'read' => $locked->refresh(),
                'rewarded' => true,
                'points_awarded' => $pointsAwarded,
                'reason' => null,
                'sticker_drop' => $stickerDrop,
            ];
        });
    }

    /**
     * Trả lý do bị chặn thưởng theo quota ngày, hoặc null nếu còn quota.
     * daily_cap: tối đa lượt rewarded/ngày; per_content_cap: tối đa/ngày/bài.
     */
    private function quotaReason(User $user, EducationalContent $content, ContentRead $current): ?string
    {
        $today = now()->toDateString();
        $dailyCap = (int) config('points.content.daily_cap', 10);
        $perContentCap = (int) config('points.content.per_content_cap', 2);

        $dailyRewarded = ContentRead::query()
            ->where('user_id', $user->id)
            ->whereDate('read_date', $today)
            ->where('rewarded', true)
            ->where('id', '!=', $current->id)
            ->count();

        if ($dailyRewarded >= $dailyCap) {
            return 'daily_cap_reached';
        }

        $contentRewarded = ContentRead::query()
            ->where('user_id', $user->id)
            ->where('content_id', $content->id)
            ->whereDate('read_date', $today)
            ->where('rewarded', true)
            ->where('id', '!=', $current->id)
            ->count();

        if ($contentRewarded >= $perContentCap) {
            return 'content_cap_reached';
        }

        return null;
    }
}
