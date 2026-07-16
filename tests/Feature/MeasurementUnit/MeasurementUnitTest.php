<?php

use App\Models\Facility;
use App\Models\MeasurementUnit;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('guest liệt kê được đơn vị đo', function () {
    MeasurementUnit::factory()->system()->count(2)->create();
    MeasurementUnit::factory()->create(['symbol' => 'xx']);

    $this->getJson('/api/measurement-units')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

it('lọc theo category=weight', function () {
    MeasurementUnit::factory()->weight()->system()->create(['symbol' => 'kg']);
    MeasurementUnit::factory()->volume()->system()->create(['symbol' => 'l']);

    $this->getJson('/api/measurement-units?category=weight')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'weight');
});

it('manager tạo được đơn vị đo custom', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->postJson('/api/measurement-units', [
        'name' => 'Tạ',
        'symbol' => 'ta',
        'category' => 'weight',
    ])->assertCreated()
        ->assertJsonPath('measurement_unit.symbol', 'ta')
        ->assertJsonPath('measurement_unit.is_system', false)
        ->assertJsonPath('message', __('measurement_units.messages.created'));

    $this->assertDatabaseHas('measurement_units', [
        'symbol' => 'ta',
        'is_system' => 0,
    ]);
});

it('user thường không tạo được đơn vị đo', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/measurement-units', [
        'name' => 'Tạ',
        'symbol' => 'ta',
        'category' => 'weight',
    ])->assertForbidden();
});

it('validate category khi tạo', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->postJson('/api/measurement-units', [
        'name' => 'Bad',
        'symbol' => 'bad',
        'category' => 'length',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['category']);
});

it('manager cập nhật được đơn vị đo', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $unit = MeasurementUnit::factory()->create(['name' => 'Cũ', 'symbol' => 'old']);

    $this->putJson("/api/measurement-units/{$unit->id}", [
        'name' => 'Mới',
    ])->assertOk()
        ->assertJsonPath('measurement_unit.name', 'Mới')
        ->assertJsonPath('message', __('measurement_units.messages.updated'));
});

it('manager xóa được đơn vị custom', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $unit = MeasurementUnit::factory()->create(['is_system' => false]);

    $this->deleteJson("/api/measurement-units/{$unit->id}")
        ->assertOk()
        ->assertJsonPath('message', __('measurement_units.messages.deleted'));

    $this->assertSoftDeleted('measurement_units', ['id' => $unit->id]);
});

it('chặn xóa đơn vị hệ thống', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $unit = MeasurementUnit::factory()->system()->create(['symbol' => 'kg']);

    $this->deleteJson("/api/measurement-units/{$unit->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['measurement_unit']);
});
