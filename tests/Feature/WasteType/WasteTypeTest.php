<?php

use App\Models\Facility;
use App\Models\User;
use App\Models\WasteType;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('guest chỉ thấy loại rác hệ thống', function () {
    WasteType::factory()->system()->count(2)->create();
    WasteType::factory()->custom()->create();

    $this->getJson('/api/waste-types')
        ->assertOk()
        ->assertJsonCount(2, 'data');

    collect($this->getJson('/api/waste-types')->json('data'))->each(function (array $row) {
        expect($row['is_system'])->toBeTrue();
    });
});

it('user thấy system + custom của mình', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    WasteType::factory()->system()->create(['name' => 'Giấy']);
    WasteType::factory()->custom($user)->create(['name' => 'Của tôi']);
    WasteType::factory()->custom($other)->create(['name' => 'Của người khác']);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/waste-types');
    $response->assertOk()->assertJsonCount(2, 'data');

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('Giấy');
    expect($names)->toContain('Của tôi');
    expect($names)->not->toContain('Của người khác');
});

it('manager thấy cả custom của người khác', function () {
    $home = Facility::factory()->create();
    $other = User::factory()->create();
    WasteType::factory()->system()->create();
    WasteType::factory()->custom($other)->create();

    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->getJson('/api/waste-types')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('user không xem chi tiết custom của người khác', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $custom = WasteType::factory()->custom($other)->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/waste-types/{$custom->id}")
        ->assertNotFound();
});

it('manager tạo được loại rác chuẩn', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->postJson('/api/waste-types', [
        'name' => 'Cao su',
        'icon' => '🛞',
        'is_system' => true,
    ])->assertCreated()
        ->assertJsonPath('waste_type.name', 'Cao su')
        ->assertJsonPath('waste_type.is_system', true)
        ->assertJsonPath('message', __('waste_types.messages.created'));
});

it('user tạo được loại rác custom', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/waste-types', [
        'name' => 'Rác nhà tôi',
        'is_system' => true,
    ])->assertCreated()
        ->assertJsonPath('waste_type.is_system', false)
        ->assertJsonPath('message', __('waste_types.messages.created'));
});

it('guest không tạo được loại rác', function () {
    $this->postJson('/api/waste-types', [
        'name' => 'X',
    ])->assertUnauthorized();
});

it('user cập nhật được custom của mình', function () {
    $user = User::factory()->create();
    $custom = WasteType::factory()->custom($user)->create(['name' => 'Cũ']);

    Sanctum::actingAs($user);

    $this->putJson("/api/waste-types/{$custom->id}", [
        'name' => 'Mới',
    ])->assertOk()
        ->assertJsonPath('waste_type.name', 'Mới');
});

it('user không cập nhật custom của người khác', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $custom = WasteType::factory()->custom($other)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/waste-types/{$custom->id}", [
        'name' => 'Hack',
    ])->assertForbidden();
});

it('user xóa được custom của mình', function () {
    $user = User::factory()->create();
    $custom = WasteType::factory()->custom($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/waste-types/{$custom->id}")
        ->assertOk()
        ->assertJsonPath('message', __('waste_types.messages.deleted'));

    $this->assertSoftDeleted('waste_types', ['id' => $custom->id]);
});

it('user không xóa được loại hệ thống', function () {
    $user = User::factory()->create();
    $system = WasteType::factory()->system()->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/waste-types/{$system->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['waste_type']);
});

it('manager xóa được loại hệ thống', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $system = WasteType::factory()->system()->create();

    $this->deleteJson("/api/waste-types/{$system->id}")
        ->assertOk();

    $this->assertSoftDeleted('waste_types', ['id' => $system->id]);
});
