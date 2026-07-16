<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\HandoverRequest;
use App\Models\HandoverWasteItem;
use App\Models\HandoverWeightLog;
use App\Models\MeasurementUnit;
use App\Models\PointEarned;
use App\Models\User;
use App\Models\WasteType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HandoverService
{
    public function __construct(private readonly WalletService $walletService)
    {
    }

    /**
     * @param  array{status?: string|null, facility_id?: int|null, per_page?: int}  $filters
     */
    public function list(User $viewer, array $filters = []): LengthAwarePaginator
    {
        $query = HandoverRequest::query()
            ->with(['wasteItems.wasteType', 'wasteItems.unit', 'facility', 'staff', 'unit'])
            ->latest('id');

        // view = full facility/all; view_own = chỉ đơn của mình.
        if ($viewer->hasPermission('handover.view')) {
            if (! empty($filters['facility_id'])) {
                $query->where('facility_id', (int) $filters['facility_id']);
            } elseif (in_array($viewer->role, ['staff', 'manager'], true) && $viewer->facility_id) {
                // Staff/manager mặc định xem đơn của cơ sở mình (trừ khi filter khác).
                $query->where('facility_id', $viewer->facility_id);
            }
        } else {
            $query->where('user_id', $viewer->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $perPage = (int) ($filters['per_page'] ?? 15);
        $perPage = max(1, min($perPage, 100));

        return $query->paginate($perPage);
    }

    public function findVisible(HandoverRequest $handover, User $viewer): HandoverRequest
    {
        if (! $this->canView($viewer, $handover)) {
            abort(404);
        }

        return $handover->load(['wasteItems.wasteType', 'wasteItems.unit', 'facility', 'staff', 'unit', 'weightLogs.unit', 'weightLogs.recorder']);
    }

    /**
     * @param  array{
     *   facility_id: int,
     *   classification_type?: string|null,
     *   estimated_weight?: float|null,
     *   unit_id?: int|null,
     *   appointment_time?: string|null,
     *   notes?: string|null,
     *   items: list<array{waste_type_id: int, weight: float|int|string, unit_id: int}>
     * }  $data
     */
    public function create(User $actor, array $data): HandoverRequest
    {
        $facility = Facility::query()->findOrFail($data['facility_id']);

        if ($facility->isLocked()) {
            throw ValidationException::withMessages([
                'facility_id' => __('handovers.messages.facility_locked'),
            ]);
        }

        $this->assertUnitExists($data['unit_id'] ?? null);
        $this->assertItemsValid($data['items'], $actor);

        return DB::transaction(function () use ($actor, $data): HandoverRequest {
            $handover = HandoverRequest::create([
                'user_id' => $actor->id,
                'facility_id' => $data['facility_id'],
                'classification_type' => $data['classification_type'] ?? null,
                'estimated_weight' => $data['estimated_weight'] ?? null,
                'unit_id' => $data['unit_id'] ?? null,
                'appointment_time' => $data['appointment_time'] ?? null,
                'expired_at' => isset($data['appointment_time'])
                    ? now()->parse($data['appointment_time'])->addDay()
                    : null,
                'status' => HandoverRequest::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncItems($handover, $data['items']);

            return $handover->load(['wasteItems.wasteType', 'wasteItems.unit', 'facility', 'unit']);
        });
    }

    /**
     * Owner cập nhật đơn pending (items + meta).
     *
     * @param  array{
     *   classification_type?: string|null,
     *   estimated_weight?: float|null,
     *   unit_id?: int|null,
     *   appointment_time?: string|null,
     *   notes?: string|null,
     *   items?: list<array{waste_type_id: int, weight: float|int|string, unit_id: int}>
     * }  $data
     */
    public function update(HandoverRequest $handover, User $actor, array $data): HandoverRequest
    {
        $this->assertOwnerOpen($handover, $actor);

        if (! $handover->isPending()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.not_editable'),
            ]);
        }

        if (array_key_exists('unit_id', $data)) {
            $this->assertUnitExists($data['unit_id']);
        }

        if (isset($data['items'])) {
            $this->assertItemsValid($data['items'], $actor);
        }

        return DB::transaction(function () use ($handover, $data): HandoverRequest {
            $fill = collect($data)->only([
                'classification_type',
                'estimated_weight',
                'unit_id',
                'appointment_time',
                'notes',
            ])->all();

            if (array_key_exists('appointment_time', $fill) && $fill['appointment_time']) {
                $fill['expired_at'] = now()->parse($fill['appointment_time'])->addDay();
            }

            $handover->fill($fill);
            $handover->save();

            if (isset($data['items'])) {
                $handover->wasteItems()->delete();
                $this->syncItems($handover, $data['items']);
            }

            return $handover->refresh()->load(['wasteItems.wasteType', 'wasteItems.unit', 'facility', 'unit']);
        });
    }

    public function cancel(HandoverRequest $handover, User $actor): HandoverRequest
    {
        if ($handover->isTerminal()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.already_closed'),
            ]);
        }

        $isOwner = $handover->user_id === $actor->id;
        $isStaffSide = $actor->hasPermission('handover.cancel') && ! $isOwner
            ? $this->canManageFacility($actor, $handover)
            : false;

        if ($isOwner) {
            if (! $actor->hasPermission('handover.cancel') && ! $actor->hasPermission('handover.view_own')) {
                abort(403);
            }
            $reason = HandoverRequest::CANCEL_USER;
        } elseif ($isStaffSide || ($actor->hasPermission('handover.cancel') && $this->canManageFacility($actor, $handover))) {
            $reason = HandoverRequest::CANCEL_STAFF;
        } else {
            abort(403);
        }

        $handover->update([
            'status' => HandoverRequest::STATUS_CANCELLED,
            'cancel_reason' => $reason,
        ]);

        return $handover->refresh();
    }

    public function approve(HandoverRequest $handover, User $actor, ?int $staffId = null): HandoverRequest
    {
        $this->assertCanManage($actor, $handover);

        if (! $handover->isPending()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.not_pending'),
            ]);
        }

        $staffId = $staffId ?? $actor->id;
        $this->assertStaffBelongsToFacility($staffId, $handover->facility_id);

        $handover->update([
            'status' => HandoverRequest::STATUS_APPROVED,
            'staff_id' => $staffId,
        ]);

        return $handover->refresh()->load(['staff', 'facility']);
    }

    public function reject(HandoverRequest $handover, User $actor, string $reason): HandoverRequest
    {
        $this->assertCanManage($actor, $handover);

        if (! $handover->isPending()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.not_pending'),
            ]);
        }

        $handover->update([
            'status' => HandoverRequest::STATUS_REJECTED,
            'reject_reason' => $reason,
        ]);

        return $handover->refresh();
    }

    public function assignStaff(HandoverRequest $handover, User $actor, int $staffId): HandoverRequest
    {
        $this->assertCanManage($actor, $handover);

        if (! $handover->isOpen()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.not_open'),
            ]);
        }

        $this->assertStaffBelongsToFacility($staffId, $handover->facility_id);

        $handover->update(['staff_id' => $staffId]);

        return $handover->refresh()->load('staff');
    }

    public function reschedule(HandoverRequest $handover, User $actor, string $appointmentTime): HandoverRequest
    {
        if ($handover->user_id !== $actor->id && ! $this->canManageFacility($actor, $handover)) {
            abort(403);
        }

        if (! $handover->isOpen()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.not_open'),
            ]);
        }

        if ($handover->reschedule_count >= HandoverRequest::MAX_RESCHEDULES) {
            $handover->update([
                'status' => HandoverRequest::STATUS_CANCELLED,
                'cancel_reason' => HandoverRequest::CANCEL_RESCHEDULE_EXCEEDED,
            ]);

            throw ValidationException::withMessages([
                'appointment_time' => __('handovers.messages.reschedule_exceeded'),
            ]);
        }

        $handover->update([
            'appointment_time' => $appointmentTime,
            'expired_at' => now()->parse($appointmentTime)->addDay(),
            'reschedule_count' => $handover->reschedule_count + 1,
        ]);

        return $handover->refresh();
    }

    /**
     * @param  array{weight: float|int|string, unit_id: int, notes?: string|null}  $data
     */
    public function recordWeight(HandoverRequest $handover, User $actor, array $data): HandoverWeightLog
    {
        $this->assertCanManage($actor, $handover);

        if (! $handover->isApproved() && ! $handover->isPending()) {
            // Cho phép cân khi approved; pending cũng OK nếu staff đã nhận.
            if ($handover->isTerminal()) {
                throw ValidationException::withMessages([
                    'handover' => __('handovers.messages.not_open'),
                ]);
            }
        }

        if ($handover->isPending()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.must_approve_before_weight'),
            ]);
        }

        $this->assertUnitExists($data['unit_id']);

        return HandoverWeightLog::create([
            'request_id' => $handover->id,
            'weight' => $data['weight'],
            'unit_id' => $data['unit_id'],
            'recorded_by' => $actor->id,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Hoàn tất đơn + cộng điểm cho owner (1 TX). Idempotent theo point_earned (handover, reference_id).
     *
     * @return array{handover: HandoverRequest, points_awarded: int}
     */
    public function complete(HandoverRequest $handover, User $actor): array
    {
        $this->assertCanManage($actor, $handover);

        if (! $handover->isApproved()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.must_approve_before_complete'),
            ]);
        }

        $logs = $handover->weightLogs()->with('unit')->get();
        if ($logs->isEmpty()) {
            throw ValidationException::withMessages([
                'handover' => __('handovers.messages.weight_required'),
            ]);
        }

        return DB::transaction(function () use ($handover, $logs): array {
            // Khóa đơn để tránh double-complete / double-earn.
            $locked = HandoverRequest::query()->whereKey($handover->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === HandoverRequest::STATUS_COMPLETED) {
                $existing = PointEarned::query()
                    ->where('source_type', PointEarned::SOURCE_HANDOVER)
                    ->where('reference_id', $locked->id)
                    ->first();

                return [
                    'handover' => $locked->load(['weightLogs.unit', 'wasteItems', 'user']),
                    'points_awarded' => $existing?->points ?? 0,
                ];
            }

            if (! $locked->isApproved()) {
                throw ValidationException::withMessages([
                    'handover' => __('handovers.messages.must_approve_before_complete'),
                ]);
            }

            $points = $this->calculateHandoverPoints($locked, $logs);

            $locked->update(['status' => HandoverRequest::STATUS_COMPLETED]);

            $owner = User::query()->findOrFail($locked->user_id);

            if ($points > 0) {
                $this->walletService->earn($owner, $points, [
                    'source_type' => PointEarned::SOURCE_HANDOVER,
                    'reference_id' => $locked->id,
                    'description' => __('handovers.messages.points_earned_description', [
                        'id' => $locked->id,
                    ]),
                ]);
            }

            return [
                'handover' => $locked->refresh()->load(['weightLogs.unit', 'wasteItems', 'user']),
                'points_awarded' => $points,
            ];
        });
    }

    /**
     * Tổng kg từ weight logs × points_per_kg × hệ số classification.
     *
     * @param  \Illuminate\Support\Collection<int, HandoverWeightLog>  $logs
     */
    public function calculateHandoverPoints(HandoverRequest $handover, $logs): int
    {
        $kg = 0.0;

        foreach ($logs as $log) {
            $kg += $this->weightLogToKg((float) $log->weight, $log->unit);
        }

        if ($kg <= 0) {
            return 0;
        }

        $perKg = (int) config('points.handover.points_per_kg', 10);
        $min = (int) config('points.handover.min_points', 1);
        $multipliers = config('points.handover.classification_multipliers', []);
        $classification = $handover->classification_type;
        $multiplier = is_string($classification) && isset($multipliers[$classification])
            ? (float) $multipliers[$classification]
            : 1.0;

        $raw = $kg * $perKg * $multiplier;
        $points = (int) round($raw);

        if ($points < $min) {
            $points = $min;
        }

        return max(0, $points);
    }

    private function weightLogToKg(float $weight, ?MeasurementUnit $unit): float
    {
        if ($weight <= 0) {
            return 0.0;
        }

        $symbol = strtolower((string) ($unit?->symbol ?? 'kg'));

        return match ($symbol) {
            'g', 'gram', 'grams' => $weight / 1000,
            't', 'ton', 'tonne', 'tấn' => $weight * 1000,
            default => $weight, // kg và unit khác: coi như kg
        };
    }


    public function canView(User $viewer, HandoverRequest $handover): bool
    {
        if ($handover->user_id === $viewer->id) {
            return true;
        }

        if ($viewer->hasPermission('handover.view') && $this->canManageFacility($viewer, $handover)) {
            return true;
        }

        return false;
    }

    private function canManageFacility(User $actor, HandoverRequest $handover): bool
    {
        if (! $actor->hasAnyPermission([
            'handover.approve',
            'handover.reject',
            'handover.assign_staff',
            'handover.complete',
            'handover.record_weight',
            'handover.cancel',
            'handover.view',
        ])) {
            return false;
        }

        // Manager/staff chỉ thao tác đơn cùng cơ sở (trừ khi không gắn facility - hiếm).
        if (in_array($actor->role, ['staff', 'manager'], true) && $actor->facility_id) {
            return (int) $actor->facility_id === (int) $handover->facility_id;
        }

        return $actor->hasPermission('handover.view');
    }

    private function assertCanManage(User $actor, HandoverRequest $handover): void
    {
        if (! $this->canManageFacility($actor, $handover)) {
            abort(403, __('permissions.messages.forbidden'));
        }
    }

    private function assertOwnerOpen(HandoverRequest $handover, User $actor): void
    {
        if ($handover->user_id !== $actor->id) {
            abort(403);
        }
    }

    private function assertStaffBelongsToFacility(int $staffId, int $facilityId): void
    {
        $staff = User::query()->find($staffId);

        if ($staff === null || ! in_array($staff->role, ['staff', 'manager'], true)) {
            throw ValidationException::withMessages([
                'staff_id' => __('handovers.messages.invalid_staff'),
            ]);
        }

        if ((int) $staff->facility_id !== (int) $facilityId) {
            throw ValidationException::withMessages([
                'staff_id' => __('handovers.messages.staff_facility_mismatch'),
            ]);
        }
    }

    private function assertUnitExists(int|string|null $unitId): void
    {
        if ($unitId === null || $unitId === '') {
            return;
        }

        if (! MeasurementUnit::query()->whereKey($unitId)->exists()) {
            throw ValidationException::withMessages([
                'unit_id' => __('handovers.messages.invalid_unit'),
            ]);
        }
    }

    /**
     * @param  list<array{waste_type_id: int, weight: mixed, unit_id: int}>  $items
     */
    private function assertItemsValid(array $items, User $actor): void
    {
        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => __('handovers.messages.items_required'),
            ]);
        }

        $typeIds = array_map(fn ($i) => (int) $i['waste_type_id'], $items);
        if (count($typeIds) !== count(array_unique($typeIds))) {
            throw ValidationException::withMessages([
                'items' => __('handovers.messages.duplicate_waste_type'),
            ]);
        }

        foreach ($items as $index => $item) {
            $type = WasteType::query()->find($item['waste_type_id']);
            if ($type === null) {
                throw ValidationException::withMessages([
                    "items.{$index}.waste_type_id" => __('handovers.messages.invalid_waste_type'),
                ]);
            }

            // Custom type chỉ owner mới dùng được.
            if (! $type->is_system && $type->created_by !== $actor->id) {
                throw ValidationException::withMessages([
                    "items.{$index}.waste_type_id" => __('handovers.messages.invalid_waste_type'),
                ]);
            }

            if (! MeasurementUnit::query()->whereKey($item['unit_id'])->exists()) {
                throw ValidationException::withMessages([
                    "items.{$index}.unit_id" => __('handovers.messages.invalid_unit'),
                ]);
            }
        }
    }

    /**
     * @param  list<array{waste_type_id: int, weight: mixed, unit_id: int}>  $items
     */
    private function syncItems(HandoverRequest $handover, array $items): void
    {
        foreach ($items as $item) {
            HandoverWasteItem::create([
                'request_id' => $handover->id,
                'waste_type_id' => $item['waste_type_id'],
                'weight' => $item['weight'],
                'unit_id' => $item['unit_id'],
            ]);
        }
    }
}
