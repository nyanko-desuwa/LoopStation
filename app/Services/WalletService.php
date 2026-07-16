<?php

namespace App\Services;

use App\Models\PointEarned;
use App\Models\PointSpent;
use App\Models\User;
use App\Models\UserWallet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    /**
     * Lấy hoặc tạo ví cho user (1-1).
     */
    public function ensureWallet(User $user): UserWallet
    {
        return UserWallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0]
        );
    }

    public function getWallet(User $user): UserWallet
    {
        return $this->ensureWallet($user);
    }

    /**
     * Cộng điểm. points > 0. Append POINT_EARNED + update balance trong 1 transaction.
     *
     * @param  array{source_type: string, reference_id?: int|null, description?: string|null}  $meta
     */
    public function earn(User $user, int $points, array $meta): PointEarned
    {
        if ($points <= 0) {
            throw ValidationException::withMessages([
                'points' => __('wallets.messages.points_positive'),
            ]);
        }

        $source = $meta['source_type'] ?? null;
        if (! in_array($source, PointEarned::SOURCES, true)) {
            throw ValidationException::withMessages([
                'source_type' => __('wallets.messages.invalid_source'),
            ]);
        }

        if ($source === PointEarned::SOURCE_MANAGER_ADJUST && empty($meta['description'])) {
            throw ValidationException::withMessages([
                'description' => __('wallets.messages.description_required'),
            ]);
        }

        return DB::transaction(function () use ($user, $points, $meta, $source): PointEarned {
            $wallet = UserWallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($user);
                $wallet = UserWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            $row = PointEarned::create([
                'wallet_id' => $wallet->id,
                'points' => $points,
                'source_type' => $source,
                'reference_id' => $meta['reference_id'] ?? null,
                'description' => $meta['description'] ?? null,
            ]);

            $wallet->increment('balance', $points);

            return $row;
        });
    }

    /**
     * Trừ điểm. points > 0. Chặn nếu balance không đủ.
     *
     * @param  array{source_type: string, reference_id?: int|null, description?: string|null}  $meta
     */
    public function spend(User $user, int $points, array $meta): PointSpent
    {
        if ($points <= 0) {
            throw ValidationException::withMessages([
                'points' => __('wallets.messages.points_positive'),
            ]);
        }

        $source = $meta['source_type'] ?? null;
        if (! in_array($source, PointSpent::SOURCES, true)) {
            throw ValidationException::withMessages([
                'source_type' => __('wallets.messages.invalid_source'),
            ]);
        }

        if ($source === PointSpent::SOURCE_MANAGER_ADJUST && empty($meta['description'])) {
            throw ValidationException::withMessages([
                'description' => __('wallets.messages.description_required'),
            ]);
        }

        return DB::transaction(function () use ($user, $points, $meta, $source): PointSpent {
            $wallet = UserWallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($wallet === null) {
                $wallet = $this->ensureWallet($user);
                $wallet = UserWallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            }

            if ($wallet->balance < $points) {
                throw ValidationException::withMessages([
                    'points' => __('wallets.messages.insufficient_balance'),
                ]);
            }

            $row = PointSpent::create([
                'wallet_id' => $wallet->id,
                'points' => $points,
                'source_type' => $source,
                'reference_id' => $meta['reference_id'] ?? null,
                'description' => $meta['description'] ?? null,
            ]);

            $wallet->decrement('balance', $points);

            return $row;
        });
    }

    /**
     * Manager điều chỉnh: direction credit|debit.
     */
    public function adjust(User $target, int $points, string $direction, string $description, User $actor): PointEarned|PointSpent
    {
        if ($direction === 'credit') {
            return $this->earn($target, $points, [
                'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
                'description' => $description,
            ]);
        }

        if ($direction === 'debit') {
            return $this->spend($target, $points, [
                'source_type' => PointSpent::SOURCE_MANAGER_ADJUST,
                'description' => $description,
            ]);
        }

        throw ValidationException::withMessages([
            'direction' => __('wallets.messages.invalid_direction'),
        ]);
    }

    /**
     * Lịch sử giao dịch gộp earned + spent, sort created_at desc.
     *
     * @return array{data: list<array<string, mixed>>, balance: int}
     */
    public function history(User $user, int $perPage = 20): array
    {
        $wallet = $this->ensureWallet($user);
        $perPage = max(1, min($perPage, 100));

        $earned = PointEarned::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($perPage)
            ->get()
            ->map(fn (PointEarned $r) => [
                'id' => $r->id,
                'type' => 'earned',
                'points' => $r->points,
                'source_type' => $r->source_type,
                'reference_id' => $r->reference_id,
                'description' => $r->description,
                'created_at' => $r->created_at,
            ]);

        $spent = PointSpent::query()
            ->where('wallet_id', $wallet->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($perPage)
            ->get()
            ->map(fn (PointSpent $r) => [
                'id' => $r->id,
                'type' => 'spent',
                'points' => $r->points,
                'source_type' => $r->source_type,
                'reference_id' => $r->reference_id,
                'description' => $r->description,
                'created_at' => $r->created_at,
            ]);

        $merged = $earned->concat($spent)
            ->sortByDesc(fn ($row) => $row['created_at']?->timestamp.'-'.$row['id'])
            ->values()
            ->take($perPage)
            ->all();

        return [
            'balance' => $wallet->fresh()->balance,
            'data' => $merged,
        ];
    }

    /**
     * Manager xem ví user bất kỳ.
     */
    public function listWallets(array $filters = []): LengthAwarePaginator
    {
        $query = UserWallet::query()->with('user')->latest('id');

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        $perPage = (int) ($filters['per_page'] ?? 20);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }
}
