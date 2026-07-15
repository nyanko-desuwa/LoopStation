<?php

use App\Models\Facility;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

// Facility write/list-manager cần RBAC seed.
beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('liệt kê cơ sở active cho guest', function () {
    Facility::factory()->count(2)->create();
    Facility::factory()->locked()->create();

    $response = $this->getJson('/api/facilities');

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    collect($response->json('data'))->each(function (array $facility) {
        expect($facility['status'])->toBe('active');
    });
});

it('manager thấy cả cơ sở locked', function () {
    $home = Facility::factory()->create();
    Facility::factory()->locked()->create();

    // Truyền facility sẵn có để factory không tự sinh thêm cơ sở.
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->getJson('/api/facilities')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('manager lọc theo status=locked', function () {
    $home = Facility::factory()->create();
    $locked = Facility::factory()->locked()->create();

    Sanctum::actingAs(User::factory()->manager($home)->create());

    $response = $this->getJson('/api/facilities?status=locked');

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $locked->id);
});

it('user thường không xem chi tiết cơ sở locked', function () {
    $locked = Facility::factory()->locked()->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/facilities/{$locked->id}")
        ->assertNotFound();
});

it('manager tạo được cơ sở', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $payload = [
        'name' => 'Trạm Quận 1',
        'type' => 'station',
        'address' => '1 Nguyễn Huệ, Q1',
        'latitude' => 10.7769000,
        'longitude' => 106.7009000,
    ];

    $response = $this->postJson('/api/facilities', $payload);

    $response->assertCreated()
        ->assertJsonPath('facility.name', 'Trạm Quận 1')
        ->assertJsonPath('facility.type', 'station')
        ->assertJsonPath('facility.status', 'active')
        ->assertJsonPath('message', __('facilities.messages.created'));

    $this->assertDatabaseHas('facilities', [
        'name' => 'Trạm Quận 1',
        'type' => 'station',
        'status' => 'active',
    ]);
});

it('user thường không tạo được cơ sở', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/facilities', [
        'name' => 'Trạm X',
        'type' => 'station',
    ])->assertForbidden();
});

it('guest không tạo được cơ sở', function () {
    $this->postJson('/api/facilities', [
        'name' => 'Trạm X',
        'type' => 'station',
    ])->assertUnauthorized();
});

it('validate type và tọa độ khi tạo', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->postJson('/api/facilities', [
        'name' => 'Bad',
        'type' => 'warehouse',
        'latitude' => 200,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'latitude']);
});

it('manager cập nhật được cơ sở', function () {
    $facility = Facility::factory()->create(['name' => 'Cũ']);

    Sanctum::actingAs(User::factory()->manager($facility)->create());

    $this->putJson("/api/facilities/{$facility->id}", [
        'name' => 'Mới',
        'status' => 'locked',
    ])->assertOk()
        ->assertJsonPath('facility.name', 'Mới')
        ->assertJsonPath('facility.status', 'locked')
        ->assertJsonPath('message', __('facilities.messages.updated'));

    $this->assertDatabaseHas('facilities', [
        'id' => $facility->id,
        'name' => 'Mới',
        'status' => 'locked',
    ]);
});

it('manager xóa được cơ sở trống', function () {
    $facility = Facility::factory()->create();
    // Manager thuộc cơ sở khác để facility đích không có staff/manager
    $other = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($other)->create());

    $this->deleteJson("/api/facilities/{$facility->id}")
        ->assertOk()
        ->assertJsonPath('message', __('facilities.messages.deleted'));

    $this->assertSoftDeleted('facilities', ['id' => $facility->id]);
});

it('chặn xóa cơ sở còn staff/manager', function () {
    $facility = Facility::factory()->create();
    User::factory()->staff($facility)->create();

    // manager khác (không thuộc facility này) thực hiện xóa
    $other = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($other)->create());

    $this->deleteJson("/api/facilities/{$facility->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['facility']);

    $this->assertDatabaseHas('facilities', [
        'id' => $facility->id,
        'deleted_at' => null,
    ]);
});

it('gắn facility_id cho staff/manager qua factory', function () {
    $facility = Facility::factory()->create();
    $staff = User::factory()->staff($facility)->create();
    $manager = User::factory()->manager($facility)->create();

    expect($staff->facility_id)->toBe($facility->id);
    expect($manager->facility_id)->toBe($facility->id);
    expect($staff->facility->is($facility))->toBeTrue();
});
