<?php

use App\Models\Facility;
use App\Models\Permission;
use App\Models\User;
use App\Services\PermissionService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('manager xem được danh mục quyền', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $this->getJson('/api/permissions')
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'code', 'resource', 'action', 'name', 'is_system']]]);
});

it('user thường không xem được danh mục quyền', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/permissions')->assertForbidden();
});

it('manager tạo được quyền custom', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $response = $this->postJson('/api/permissions', [
        'resource' => 'report',
        'action' => 'export',
        'name' => 'Xuất báo cáo',
        'description' => 'Xuất CSV',
    ]);

    $response->assertCreated()
        ->assertJsonPath('permission.code', 'report.export')
        ->assertJsonPath('permission.is_system', false)
        ->assertJsonPath('message', __('permissions.messages.created'));

    $this->assertDatabaseHas('permissions', [
        'code' => 'report.export',
        'is_system' => 0,
    ]);
});

it('manager cập nhật name/description của quyền', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $permission = Permission::query()->where('code', 'facility.view')->firstOrFail();

    $this->putJson("/api/permissions/{$permission->id}", [
        'name' => 'Xem cơ sở (mới)',
        'description' => 'Mô tả mới',
    ])->assertOk()
        ->assertJsonPath('permission.name', 'Xem cơ sở (mới)')
        ->assertJsonPath('message', __('permissions.messages.updated'));
});

it('chặn xóa quyền hệ thống', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $permission = Permission::query()->where('code', 'facility.view')->firstOrFail();

    $this->deleteJson("/api/permissions/{$permission->id}")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['permission']);
});

it('xóa được quyền custom', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $permission = Permission::factory()->create([
        'code' => 'report.export',
        'resource' => 'report',
        'action' => 'export',
        'name' => 'Export',
        'is_system' => false,
    ]);

    $this->deleteJson("/api/permissions/{$permission->id}")
        ->assertOk()
        ->assertJsonPath('message', __('permissions.messages.deleted'));

    $this->assertDatabaseMissing('permissions', ['id' => $permission->id]);
});

it('manager xem mapping quyền của role', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $response = $this->getJson('/api/roles/manager/permissions');

    $response->assertOk()
        ->assertJsonPath('role', 'manager')
        ->assertJsonStructure(['codes', 'permissions']);

    expect($response->json('codes'))->toContain('facility.create');
    expect($response->json('codes'))->toContain('permission.view');
});

it('manager sync mapping role', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $ids = Permission::query()
        ->whereIn('code', ['facility.view', 'auth.login'])
        ->pluck('id')
        ->all();

    $response = $this->putJson('/api/roles/user/permissions', [
        'permission_ids' => $ids,
    ]);

    $response->assertOk()
        ->assertJsonPath('role', 'user')
        ->assertJsonPath('message', __('permissions.messages.role_synced'));

    $codes = $response->json('codes');
    expect($codes)->toContain('facility.view');
    expect($codes)->toContain('auth.login');
    expect($codes)->not->toContain('facility.create');
});

it('user lấy được quyền của chính mình', function () {
    Sanctum::actingAs(User::factory()->create());

    $response = $this->getJson('/api/auth/me/permissions');

    $response->assertOk()
        ->assertJsonPath('role', 'user')
        ->assertJsonStructure(['codes']);

    expect($response->json('codes'))->toContain('facility.view');
    expect($response->json('codes'))->not->toContain('facility.create');
});

it('hasPermission hoạt động theo role mapping', function () {
    $home = Facility::factory()->create();
    $manager = User::factory()->manager($home)->create();
    $user = User::factory()->create();

    expect($manager->hasPermission('facility.create'))->toBeTrue();
    expect($user->hasPermission('facility.create'))->toBeFalse();
    expect($user->hasPermission('facility.view'))->toBeTrue();
});

it('flush cache khi sync role', function () {
    $service = app(PermissionService::class);

    // Warm cache
    expect($service->roleHas('user', 'facility.view'))->toBeTrue();

    $ids = Permission::query()
        ->where('code', 'auth.login')
        ->pluck('id')
        ->all();

    $service->syncRolePermissions('user', $ids);

    expect($service->roleHas('user', 'facility.view'))->toBeFalse();
    expect($service->roleHas('user', 'auth.login'))->toBeTrue();
});
