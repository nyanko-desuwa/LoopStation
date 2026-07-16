<?php

namespace App\Services;

use App\Models\EducationalContent;
use App\Models\PointEarned;
use App\Models\Sticker;
use App\Models\StickerObtainLog;
use App\Models\StickerSet;
use App\Models\User;
use App\Models\UserSticker;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StickerService
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * @param  array{status?: string|null, search?: string|null, per_page?: int}  $filters
     */
    public function listSets(array $filters = [], bool $asManager = false): LengthAwarePaginator
    {
        $query = StickerSet::query()->withCount('stickers')->latest('id');

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->active();
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('theme', 'like', "%{$search}%");
            });
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function findSetVisible(StickerSet $set, bool $asManager = false): StickerSet
    {
        if (! $asManager && ! $set->isActive()) {
            abort(404);
        }

        return $set->load(['stickers' => function ($q) use ($asManager): void {
            if (! $asManager) {
                $q->active();
            }
            $q->orderBy('id');
        }]);
    }

    /**
     * @param  array{name: string, theme?: string|null, cover_image_url?: string|null, status?: string}  $data
     */
    public function createSet(array $data): StickerSet
    {
        return StickerSet::create([
            'name' => $data['name'],
            'theme' => $data['theme'] ?? null,
            'cover_image_url' => $data['cover_image_url'] ?? null,
            'status' => $data['status'] ?? StickerSet::STATUS_ACTIVE,
        ]);
    }

    /**
     * @param  array{name?: string, theme?: string|null, cover_image_url?: string|null, status?: string}  $data
     */
    public function updateSet(StickerSet $set, array $data): StickerSet
    {
        $set->fill(collect($data)->only([
            'name',
            'theme',
            'cover_image_url',
            'status',
        ])->all());
        $set->save();

        return $set->refresh();
    }

    public function deleteSet(StickerSet $set): void
    {
        $set->delete();
    }

    /**
     * @param  array{set_id?: int|null, status?: string|null, rarity?: string|null, search?: string|null, per_page?: int}  $filters
     */
    public function listStickers(array $filters = [], bool $asManager = false): LengthAwarePaginator
    {
        $query = Sticker::query()->with('set')->latest('id');

        if (! empty($filters['set_id'])) {
            $query->where('set_id', (int) $filters['set_id']);
        }

        if ($asManager) {
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            $query->active()->whereHas('set', fn ($q) => $q->active());
        }

        if (! empty($filters['rarity'])) {
            $query->where('rarity', $filters['rarity']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function findStickerVisible(Sticker $sticker, bool $asManager = false): Sticker
    {
        $sticker->loadMissing('set');

        if (! $asManager && (! $sticker->isActive() || ! $sticker->set?->isActive())) {
            abort(404);
        }

        return $sticker;
    }

    /**
     * @param  array{
     *   set_id: int,
     *   name: string,
     *   image_url: string,
     *   rarity?: string,
     *   drop_weight?: int,
     *   redeem_quantity_required?: int,
     *   bonus_points?: int,
     *   unlocks_content_id?: int|null,
     *   status?: string
     * }  $data
     */
    public function createSticker(array $data): Sticker
    {
        $this->assertSetExists((int) $data['set_id']);
        $this->assertUnlockContent($data['unlocks_content_id'] ?? null);

        return Sticker::create([
            'set_id' => $data['set_id'],
            'name' => $data['name'],
            'image_url' => $data['image_url'],
            'rarity' => $data['rarity'] ?? Sticker::RARITY_COMMON,
            'drop_weight' => max(1, (int) ($data['drop_weight'] ?? 1)),
            'redeem_quantity_required' => max(1, (int) ($data['redeem_quantity_required'] ?? 1)),
            'bonus_points' => max(0, (int) ($data['bonus_points'] ?? 0)),
            'unlocks_content_id' => $data['unlocks_content_id'] ?? null,
            'status' => $data['status'] ?? Sticker::STATUS_ACTIVE,
        ])->load('set');
    }

    /**
     * @param  array{
     *   set_id?: int,
     *   name?: string,
     *   image_url?: string,
     *   rarity?: string,
     *   drop_weight?: int,
     *   redeem_quantity_required?: int,
     *   bonus_points?: int,
     *   unlocks_content_id?: int|null,
     *   status?: string
     * }  $data
     */
    public function updateSticker(Sticker $sticker, array $data): Sticker
    {
        if (array_key_exists('set_id', $data)) {
            $this->assertSetExists((int) $data['set_id']);
        }
        if (array_key_exists('unlocks_content_id', $data)) {
            $this->assertUnlockContent($data['unlocks_content_id']);
        }

        $payload = collect($data)->only([
            'set_id',
            'name',
            'image_url',
            'rarity',
            'drop_weight',
            'redeem_quantity_required',
            'bonus_points',
            'unlocks_content_id',
            'status',
        ])->all();

        if (isset($payload['drop_weight'])) {
            $payload['drop_weight'] = max(1, (int) $payload['drop_weight']);
        }
        if (isset($payload['redeem_quantity_required'])) {
            $payload['redeem_quantity_required'] = max(1, (int) $payload['redeem_quantity_required']);
        }
        if (isset($payload['bonus_points'])) {
            $payload['bonus_points'] = max(0, (int) $payload['bonus_points']);
        }

        $sticker->fill($payload);
        $sticker->save();

        return $sticker->refresh()->load('set');
    }

    public function deleteSticker(Sticker $sticker): void
    {
        $sticker->delete();
    }

    /**
     * Inventory của user (own) hoặc staff xem (sticker.view).
     *
     * @param  array{per_page?: int}  $filters
     */
    public function listUserInventory(User $owner, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 50);
        $perPage = max(1, min($perPage, 100));

        return UserSticker::query()
            ->with(['sticker.set'])
            ->where('user_id', $owner->id)
            ->where('quantity', '>', 0)
            ->latest('last_obtained_at')
            ->paginate($perPage);
    }

    /**
     * Lịch sử nhận sticker.
     *
     * @param  array{per_page?: int}  $filters
     */
    public function listObtainLogs(User $owner, array $filters = []): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        return StickerObtainLog::query()
            ->with(['sticker.set', 'sourceContent'])
            ->where('user_id', $owner->id)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Drop sticker khi user complete read (rewarded).
     * Weighted random trong set active; locked set/sticker bỏ qua.
     * Idempotent theo content_read: 1 read tối đa 1 drop (check obtain log by content + user gần đúng qua source_content_id + recent? dùng reference trong log không có read_id).
     * Schema chỉ có source_content_id - dùng check: không drop lần 2 nếu đã có obtain log cho (user, source_content_id) trong cùng lần complete...
     * Thực tế 1 content có thể đọc nhiều lần/ngày → mỗi complete rewarded drop 1 lần.
     * Gọi từ completeRead sau khi rewarded; caller đảm bảo chỉ 1 lần per complete.
     *
     * @return array{sticker: Sticker|null, first_owned: bool, bonus_points: int, unlocked_content_id: int|null}
     */
    public function dropFromContent(User $user, EducationalContent $content): array
    {
        $empty = [
            'sticker' => null,
            'first_owned' => false,
            'bonus_points' => 0,
            'unlocked_content_id' => null,
        ];

        if ($content->sticker_set_id === null) {
            return $empty;
        }

        $set = StickerSet::query()->find($content->sticker_set_id);
        if ($set === null || ! $set->isActive() || $set->trashed()) {
            return $empty;
        }

        /** @var Collection<int, Sticker> $pool */
        $pool = Sticker::query()
            ->where('set_id', $set->id)
            ->active()
            ->where('drop_weight', '>', 0)
            ->get();

        if ($pool->isEmpty()) {
            return $empty;
        }

        $picked = $this->weightedPick($pool);
        if ($picked === null) {
            return $empty;
        }

        return $this->grantSticker($user, $picked, $content->id);
    }

    /**
     * Cấp sticker: inventory + log + bonus lần đầu.
     * Nên gọi trong transaction đang mở (completeRead).
     *
     * @return array{sticker: Sticker, first_owned: bool, bonus_points: int, unlocked_content_id: int|null}
     */
    public function grantSticker(User $user, Sticker $sticker, ?int $sourceContentId = null): array
    {
        $now = now();

        $row = UserSticker::query()
            ->where('user_id', $user->id)
            ->where('sticker_id', $sticker->id)
            ->lockForUpdate()
            ->first();

        $firstOwned = false;

        if ($row === null) {
            $firstOwned = true;
            $row = UserSticker::create([
                'user_id' => $user->id,
                'sticker_id' => $sticker->id,
                'quantity' => 1,
                'total_obtained' => 1,
                'first_obtained_at' => $now,
                'last_obtained_at' => $now,
            ]);
        } else {
            // total_obtained 0 → 1 cũng coi first (edge case).
            if ($row->total_obtained <= 0) {
                $firstOwned = true;
                $row->first_obtained_at = $now;
            }
            $row->quantity += 1;
            $row->total_obtained += 1;
            $row->last_obtained_at = $now;
            $row->save();
        }

        StickerObtainLog::create([
            'user_id' => $user->id,
            'sticker_id' => $sticker->id,
            'source_content_id' => $sourceContentId,
        ]);

        $bonusAwarded = 0;
        $unlockedContentId = null;

        if ($firstOwned) {
            if ($sticker->bonus_points > 0) {
                $this->walletService->earn($user, $sticker->bonus_points, [
                    'source_type' => PointEarned::SOURCE_STICKER_BONUS,
                    'reference_id' => $sticker->id,
                    'description' => __('stickers.messages.bonus_points_description', [
                        'name' => $sticker->name,
                    ]),
                ]);
                $bonusAwarded = $sticker->bonus_points;
            }

            if ($sticker->unlocks_content_id !== null) {
                $unlockedContentId = $sticker->unlocks_content_id;
            }
        }

        return [
            'sticker' => $sticker->load('set'),
            'first_owned' => $firstOwned,
            'bonus_points' => $bonusAwarded,
            'unlocked_content_id' => $unlockedContentId,
        ];
    }

    /**
     * @param  Collection<int, Sticker>  $pool
     */
    private function weightedPick(Collection $pool): ?Sticker
    {
        $total = (int) $pool->sum(fn (Sticker $s) => max(0, $s->drop_weight));
        if ($total <= 0) {
            return null;
        }

        $roll = random_int(1, $total);
        $cursor = 0;

        foreach ($pool as $sticker) {
            $cursor += max(0, $sticker->drop_weight);
            if ($roll <= $cursor) {
                return $sticker;
            }
        }

        return $pool->last();
    }

    private function assertSetExists(int $setId): void
    {
        if (! StickerSet::query()->whereKey($setId)->exists()) {
            throw ValidationException::withMessages([
                'set_id' => __('stickers.messages.set_not_found'),
            ]);
        }
    }

    private function assertUnlockContent(mixed $contentId): void
    {
        if ($contentId === null || $contentId === '') {
            return;
        }

        if (! EducationalContent::query()->whereKey((int) $contentId)->exists()) {
            throw ValidationException::withMessages([
                'unlocks_content_id' => __('stickers.messages.content_not_found'),
            ]);
        }
    }
}
