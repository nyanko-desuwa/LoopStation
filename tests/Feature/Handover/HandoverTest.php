<?php

use App\Models\Facility;
use App\Models\HandoverRequest;
use App\Models\MeasurementUnit;
use App\Models\User;
use App\Models\WasteType;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->facility = Facility::factory()->create();
    $this->unit = MeasurementUnit::factory()->system()->weight()->create(['symbol' => 'kg']);
    $this->wasteA = WasteType::factory()->system()->create(['name' => 'Giấy']);
    $this->wasteB = WasteType::factory()->system()->create(['name' => 'Nhựa']);
});

function handoverPayload($facility, $unit, $wasteA, $wasteB, array $extra = []): array
{
    return array_merge([
        'facility_id' => $facility->id,
        'classification_type' => 'cleaned',
        'estimated_weight' => 5.5,
        'unit_id' => $unit->id,
        'appointment_time' => now()->addDays(2)->toISOString(),
        'notes' => 'Ghi chú test',
        'items' => [
            ['waste_type_id' => $wasteA->id, 'weight' => 3, 'unit_id' => $unit->id],
            ['waste_type_id' => $wasteB->id, 'weight' => 2.5, 'unit_id' => $unit->id],
        ],
    ], $extra);
}

it('user tạo được đơn chuyển giao kèm items', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->postJson('/api/handovers', handoverPayload(
        $this->facility,
        $this->unit,
        $this->wasteA,
        $this->wasteB
    ));

    $response->assertCreated()
        ->assertJsonPath('handover.status', 'pending')
        ->assertJsonPath('handover.facility_id', $this->facility->id)
        ->assertJsonCount(2, 'handover.items')
        ->assertJsonPath('message', __('handovers.messages.created'));

    $this->assertDatabaseHas('handover_requests', [
        'facility_id' => $this->facility->id,
        'status' => 'pending',
    ]);
    $this->assertDatabaseCount('handover_waste_items', 2);
});

it('guest không tạo được đơn', function () {
    $this->postJson('/api/handovers', handoverPayload(
        $this->facility,
        $this->unit,
        $this->wasteA,
        $this->wasteB
    ))->assertUnauthorized();
});

it('chặn tạo đơn vào cơ sở locked', function () {
    Sanctum::actingAs(User::factory()->create());
    $locked = Facility::factory()->locked()->create();

    $this->postJson('/api/handovers', handoverPayload(
        $locked,
        $this->unit,
        $this->wasteA,
        $this->wasteB
    ))->assertStatus(422)
        ->assertJsonValidationErrors(['facility_id']);
});

it('chặn items trùng waste_type', function () {
    Sanctum::actingAs(User::factory()->create());

    $payload = handoverPayload($this->facility, $this->unit, $this->wasteA, $this->wasteB);
    $payload['items'] = [
        ['waste_type_id' => $this->wasteA->id, 'weight' => 1, 'unit_id' => $this->unit->id],
        ['waste_type_id' => $this->wasteA->id, 'weight' => 2, 'unit_id' => $this->unit->id],
    ];

    $this->postJson('/api/handovers', $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['items.1.waste_type_id']);
});

it('user chỉ thấy đơn của mình', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    HandoverRequest::factory()->forUser($user)->atFacility($this->facility)->create();
    HandoverRequest::factory()->forUser($other)->atFacility($this->facility)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/handovers')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('staff thấy đơn của cơ sở mình', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $otherFacility = Facility::factory()->create();

    HandoverRequest::factory()->atFacility($this->facility)->create();
    HandoverRequest::factory()->atFacility($otherFacility)->create();

    Sanctum::actingAs($staff);

    $this->getJson('/api/handovers')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

it('user cập nhật được đơn pending của mình', function () {
    $user = User::factory()->create();
    $handover = HandoverRequest::factory()->forUser($user)->atFacility($this->facility)->pending()->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/handovers/{$handover->id}", [
        'notes' => 'Sửa ghi chú',
        'items' => [
            ['waste_type_id' => $this->wasteA->id, 'weight' => 1.5, 'unit_id' => $this->unit->id],
        ],
    ])->assertOk()
        ->assertJsonPath('handover.notes', 'Sửa ghi chú')
        ->assertJsonCount(1, 'handover.items');
});

it('user hủy được đơn của mình', function () {
    $user = User::factory()->create();
    $handover = HandoverRequest::factory()->forUser($user)->atFacility($this->facility)->pending()->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/handovers/{$handover->id}/cancel")
        ->assertOk()
        ->assertJsonPath('handover.status', 'cancelled')
        ->assertJsonPath('handover.cancel_reason', 'user_cancel');
});

it('staff duyệt đơn và gán chính mình', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $user = User::factory()->create();
    $handover = HandoverRequest::factory()->forUser($user)->atFacility($this->facility)->pending()->create();

    Sanctum::actingAs($staff);

    $this->postJson("/api/handovers/{$handover->id}/approve")
        ->assertOk()
        ->assertJsonPath('handover.status', 'approved')
        ->assertJsonPath('handover.staff_id', $staff->id);
});

it('staff từ chối đơn kèm lý do', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $handover = HandoverRequest::factory()->atFacility($this->facility)->pending()->create();

    Sanctum::actingAs($staff);

    $this->postJson("/api/handovers/{$handover->id}/reject", [
        'reject_reason' => 'Rác không đạt',
    ])->assertOk()
        ->assertJsonPath('handover.status', 'rejected')
        ->assertJsonPath('handover.reject_reason', 'Rác không đạt');
});

it('chặn gán staff khác cơ sở', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $otherFacility = Facility::factory()->create();
    $foreignStaff = User::factory()->staff($otherFacility)->create();
    $handover = HandoverRequest::factory()->atFacility($this->facility)->pending()->create();

    Sanctum::actingAs($manager);

    $this->postJson("/api/handovers/{$handover->id}/assign-staff", [
        'staff_id' => $foreignStaff->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['staff_id']);
});

it('ghi cân và hoàn tất đơn', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $handover = HandoverRequest::factory()
        ->atFacility($this->facility)
        ->approved($staff)
        ->create();

    Sanctum::actingAs($staff);

    $this->postJson("/api/handovers/{$handover->id}/weight-logs", [
        'weight' => 4.2,
        'unit_id' => $this->unit->id,
        'notes' => 'Lần 1',
    ])->assertCreated()
        ->assertJsonPath('weight_log.weight', '4.20')
        ->assertJsonPath('message', __('handovers.messages.weight_recorded'));

    $this->postJson("/api/handovers/{$handover->id}/complete")
        ->assertOk()
        ->assertJsonPath('handover.status', 'completed');
});

it('chặn hoàn tất khi chưa có weight log', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $handover = HandoverRequest::factory()
        ->atFacility($this->facility)
        ->approved($staff)
        ->create();

    Sanctum::actingAs($staff);

    $this->postJson("/api/handovers/{$handover->id}/complete")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['handover']);
});

it('dời lịch tăng reschedule_count; lần 3 hủy đơn', function () {
    $user = User::factory()->create();
    $handover = HandoverRequest::factory()
        ->forUser($user)
        ->atFacility($this->facility)
        ->pending()
        ->create(['reschedule_count' => 0]);

    Sanctum::actingAs($user);

    $this->postJson("/api/handovers/{$handover->id}/reschedule", [
        'appointment_time' => now()->addDays(3)->toISOString(),
    ])->assertOk()
        ->assertJsonPath('handover.reschedule_count', 1);

    $this->postJson("/api/handovers/{$handover->id}/reschedule", [
        'appointment_time' => now()->addDays(4)->toISOString(),
    ])->assertOk()
        ->assertJsonPath('handover.reschedule_count', 2);

    $this->postJson("/api/handovers/{$handover->id}/reschedule", [
        'appointment_time' => now()->addDays(5)->toISOString(),
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['appointment_time']);

    $this->assertDatabaseHas('handover_requests', [
        'id' => $handover->id,
        'status' => 'cancelled',
        'cancel_reason' => 'reschedule_exceeded',
    ]);
});

it('user khác không xem được đơn của người khác', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $handover = HandoverRequest::factory()->forUser($owner)->atFacility($this->facility)->create();

    Sanctum::actingAs($other);

    $this->getJson("/api/handovers/{$handover->id}")
        ->assertNotFound();
});
