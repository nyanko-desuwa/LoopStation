<?php

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\EventReward;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->facility = Facility::factory()->create();
});

it('guest chỉ thấy upcoming/active', function () {
    Event::factory()->upcoming()->create();
    Event::factory()->active()->create();
    Event::factory()->ended()->create();
    Event::factory()->cancelled()->create();

    $this->getJson('/api/events')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('manager tạo được sự kiện', function () {
    Sanctum::actingAs(User::factory()->manager($this->facility)->create());

    $payload = [
        'title' => 'Ngày hội sống xanh Q1',
        'location' => 'Công viên 23/9',
        'start_time' => now()->addDays(3)->toISOString(),
        'end_time' => now()->addDays(3)->addHours(4)->toISOString(),
    ];

    $this->postJson('/api/events', $payload)
        ->assertCreated()
        ->assertJsonPath('event.title', 'Ngày hội sống xanh Q1')
        ->assertJsonPath('event.status', 'upcoming')
        ->assertJsonPath('message', __('events.messages.created'));

    $this->assertDatabaseHas('events', ['title' => 'Ngày hội sống xanh Q1']);
});

it('user không tạo được sự kiện', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/events', [
        'title' => 'X',
        'location' => 'Y',
        'start_time' => now()->addDay()->toISOString(),
        'end_time' => now()->addDay()->addHour()->toISOString(),
    ])->assertForbidden();
});

it('validate end_time sau start_time', function () {
    Sanctum::actingAs(User::factory()->manager($this->facility)->create());

    $this->postJson('/api/events', [
        'title' => 'Bad',
        'location' => 'X',
        'start_time' => now()->addDays(2)->toISOString(),
        'end_time' => now()->addDay()->toISOString(),
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['end_time']);
});

it('manager kích hoạt và kết thúc sự kiện', function () {
    Sanctum::actingAs(User::factory()->manager($this->facility)->create());
    $event = Event::factory()->upcoming()->create();

    $this->postJson("/api/events/{$event->id}/activate")
        ->assertOk()
        ->assertJsonPath('event.status', 'active');

    $this->postJson("/api/events/{$event->id}/end")
        ->assertOk()
        ->assertJsonPath('event.status', 'ended');
});

it('manager phân công staff cùng cơ sở', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $staff = User::factory()->staff($this->facility)->create();
    $event = Event::factory()->upcoming()->create();

    Sanctum::actingAs($manager);

    $this->postJson("/api/events/{$event->id}/staff", [
        'staff_id' => $staff->id,
    ])->assertCreated()
        ->assertJsonPath('assignment.staff_id', $staff->id);

    $this->assertDatabaseHas('event_staff_assignments', [
        'event_id' => $event->id,
        'staff_id' => $staff->id,
    ]);
});

it('chặn phân công staff khác cơ sở', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $other = Facility::factory()->create();
    $foreignStaff = User::factory()->staff($other)->create();
    $event = Event::factory()->upcoming()->create();

    Sanctum::actingAs($manager);

    $this->postJson("/api/events/{$event->id}/staff", [
        'staff_id' => $foreignStaff->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['staff_id']);
});

it('chặn phân công staff overlap thời gian', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $staff = User::factory()->staff($this->facility)->create();

    $start = now()->addDays(5)->setTime(8, 0);
    $eventA = Event::factory()->create([
        'start_time' => $start,
        'end_time' => (clone $start)->addHours(4),
        'status' => 'upcoming',
    ]);
    $eventB = Event::factory()->create([
        'start_time' => (clone $start)->addHours(2),
        'end_time' => (clone $start)->addHours(6),
        'status' => 'upcoming',
    ]);

    Sanctum::actingAs($manager);

    $this->postJson("/api/events/{$eventA->id}/staff", ['staff_id' => $staff->id])
        ->assertCreated();

    $this->postJson("/api/events/{$eventB->id}/staff", ['staff_id' => $staff->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['staff_id']);
});

it('manager thêm/sửa/xóa quà sự kiện', function () {
    Sanctum::actingAs(User::factory()->manager($this->facility)->create());
    $event = Event::factory()->upcoming()->create();

    $create = $this->postJson("/api/events/{$event->id}/rewards", [
        'name' => 'Bình nước',
        'quantity' => 20,
    ])->assertCreated()
        ->assertJsonPath('reward.remaining', 20);

    $rewardId = $create->json('reward.id');

    $this->putJson("/api/events/{$event->id}/rewards/{$rewardId}", [
        'remaining' => 15,
    ])->assertOk()
        ->assertJsonPath('reward.remaining', 15);

    $this->deleteJson("/api/events/{$event->id}/rewards/{$rewardId}")
        ->assertOk();

    $this->assertDatabaseMissing('event_rewards', ['id' => $rewardId]);
});

it('user đăng ký sự kiện', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->upcoming()->create();

    $this->postJson("/api/events/{$event->id}/registrations", [
        'registration_type' => 'visit',
    ])->assertCreated()
        ->assertJsonPath('registration.status', 'registered')
        ->assertJsonPath('message', __('events.messages.registered'));

    // Đăng ký lần 2 bị chặn.
    $this->postJson("/api/events/{$event->id}/registrations")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['event']);
});

it('user check-in bằng QR khi event active', function () {
    $user = User::factory()->create();
    $event = Event::factory()->active()->create();

    Sanctum::actingAs($user);

    // Walk-in check-in (chưa đăng ký trước).
    $this->postJson('/api/events/check-in', [
        'qr_code' => $event->qr_code,
    ])->assertOk()
        ->assertJsonPath('registration.registration_type', 'walkin')
        ->assertJsonPath('registration.status', 'attended');
});

it('QR inactive khi event upcoming', function () {
    Sanctum::actingAs(User::factory()->create());
    $event = Event::factory()->upcoming()->create();

    $this->postJson('/api/events/check-in', [
        'qr_code' => $event->qr_code,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['qr_code']);
});

it('staff đánh dấu vắng mặt', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $event = Event::factory()->active()->create();
    $registration = EventRegistration::factory()->forEvent($event)->create();

    Sanctum::actingAs($staff);

    $this->postJson("/api/events/{$event->id}/registrations/{$registration->id}/absent")
        ->assertOk()
        ->assertJsonPath('registration.status', 'absent');
});

it('staff mở khóa minigame', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $event = Event::factory()->active()->create();
    $registration = EventRegistration::factory()->forEvent($event)->attended()->create();

    Sanctum::actingAs($staff);

    $this->postJson("/api/events/{$event->id}/registrations/{$registration->id}/unlock-minigame")
        ->assertOk()
        ->assertJsonPath('registration.minigame_status', 'unlocked');
});

it('user hủy đăng ký khi chưa check-in', function () {
    $user = User::factory()->create();
    $event = Event::factory()->upcoming()->create();
    $registration = EventRegistration::factory()->forEvent($event)->forUser($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/events/{$event->id}/registrations/{$registration->id}")
        ->assertOk();

    $this->assertDatabaseMissing('event_registrations', ['id' => $registration->id]);
});

it('FK handover.event_id trỏ events', function () {
    $event = Event::factory()->active()->create();
    $user = User::factory()->create();

    $handover = \App\Models\HandoverRequest::factory()
        ->forUser($user)
        ->atFacility($this->facility)
        ->create(['event_id' => $event->id]);

    expect($handover->fresh()->event_id)->toBe($event->id);
    $this->assertDatabaseHas('handover_requests', [
        'id' => $handover->id,
        'event_id' => $event->id,
    ]);
});
