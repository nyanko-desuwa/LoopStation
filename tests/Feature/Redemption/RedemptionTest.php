<?php

use App\Models\Facility;
use App\Models\PointEarned;
use App\Models\Redemption;
use App\Models\RewardCatalog;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('guest thấy danh mục quà active', function () {
    RewardCatalog::factory()->count(2)->create();
    RewardCatalog::factory()->locked()->create();

    $this->getJson('/api/rewards')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('manager tạo/cập nhật quà', function () {
    $home = Facility::factory()->create();
    Sanctum::actingAs(User::factory()->manager($home)->create());

    $create = $this->postJson('/api/rewards', [
        'name' => 'Túi vải',
        'points_cost' => 50,
        'stock' => 10,
    ])->assertCreated()
        ->assertJsonPath('reward.name', 'Túi vải')
        ->assertJsonPath('reward.points_cost', 50);

    $id = $create->json('reward.id');

    $this->putJson("/api/rewards/{$id}", [
        'stock' => 8,
        'status' => 'locked',
    ])->assertOk()
        ->assertJsonPath('reward.stock', 8)
        ->assertJsonPath('reward.status', 'locked');
});

it('user đổi quà pickup: trừ điểm + giảm stock', function () {
    $user = User::factory()->create();
    $wallet = app(WalletService::class);
    $wallet->earn($user, 200, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Nạp test',
    ]);

    $reward = RewardCatalog::factory()->create([
        'points_cost' => 40,
        'stock' => 5,
        'status' => 'active',
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/redemptions', [
        'reward_id' => $reward->id,
        'quantity' => 2,
        'fulfillment_method' => 'pickup',
    ])->assertCreated()
        ->assertJsonPath('redemption.points_spent', 80)
        ->assertJsonPath('redemption.quantity', 2)
        ->assertJsonPath('redemption.status', 'pending')
        ->assertJsonPath('message', __('redemptions.messages.created'));

    expect($wallet->getWallet($user)->fresh()->balance)->toBe(120);
    expect($reward->fresh()->stock)->toBe(3);

    $this->assertDatabaseHas('point_spent', [
        'points' => 80,
        'source_type' => 'redemption',
    ]);
});

it('chặn đổi khi hết hàng', function () {
    $user = User::factory()->create();
    app(WalletService::class)->earn($user, 100, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Nạp',
    ]);

    $reward = RewardCatalog::factory()->outOfStock()->create(['points_cost' => 10]);

    Sanctum::actingAs($user);

    $this->postJson('/api/redemptions', [
        'reward_id' => $reward->id,
        'fulfillment_method' => 'pickup',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['quantity']);
});

it('chặn đổi khi không đủ điểm', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);
    $reward = RewardCatalog::factory()->create(['points_cost' => 100, 'stock' => 5]);

    Sanctum::actingAs($user);

    $this->postJson('/api/redemptions', [
        'reward_id' => $reward->id,
        'fulfillment_method' => 'pickup',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['points']);
});

it('delivery bắt buộc thông tin người nhận', function () {
    $user = User::factory()->create();
    app(WalletService::class)->earn($user, 100, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Nạp',
    ]);
    $reward = RewardCatalog::factory()->create(['points_cost' => 20, 'stock' => 3]);

    Sanctum::actingAs($user);

    $this->postJson('/api/redemptions', [
        'reward_id' => $reward->id,
        'fulfillment_method' => 'delivery',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['recipient_name', 'recipient_phone', 'shipping_address']);
});

it('user hủy đơn: hoàn điểm + hoàn stock', function () {
    $user = User::factory()->create();
    $wallet = app(WalletService::class);
    $wallet->earn($user, 100, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Nạp',
    ]);
    $reward = RewardCatalog::factory()->create(['points_cost' => 30, 'stock' => 4]);

    Sanctum::actingAs($user);

    $id = $this->postJson('/api/redemptions', [
        'reward_id' => $reward->id,
        'quantity' => 1,
        'fulfillment_method' => 'pickup',
    ])->assertCreated()->json('redemption.id');

    expect($wallet->getWallet($user)->fresh()->balance)->toBe(70);
    expect($reward->fresh()->stock)->toBe(3);

    $this->postJson("/api/redemptions/{$id}/cancel")
        ->assertOk()
        ->assertJsonPath('redemption.status', 'cancelled');

    expect($wallet->getWallet($user)->fresh()->balance)->toBe(100);
    expect($reward->fresh()->stock)->toBe(4);

    $this->assertDatabaseHas('point_earned', [
        'source_type' => 'redemption_refund',
        'points' => 30,
    ]);
});

it('staff fulfill đơn pickup', function () {
    $home = Facility::factory()->create();
    $staff = User::factory()->staff($home)->create();
    $user = User::factory()->create();
    app(WalletService::class)->earn($user, 50, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Nạp',
    ]);
    $reward = RewardCatalog::factory()->create(['points_cost' => 20, 'stock' => 2]);

    Sanctum::actingAs($user);
    $id = $this->postJson('/api/redemptions', [
        'reward_id' => $reward->id,
        'fulfillment_method' => 'pickup',
    ])->json('redemption.id');

    Sanctum::actingAs($staff);
    $this->postJson("/api/redemptions/{$id}/fulfill")
        ->assertOk()
        ->assertJsonPath('redemption.status', 'fulfilled')
        ->assertJsonPath('redemption.fulfilled_by_id', $staff->id);
});

it('user chỉ thấy đơn của mình', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    Redemption::factory()->forUser($a)->create();
    Redemption::factory()->forUser($b)->create();

    Sanctum::actingAs($a);

    $this->getJson('/api/redemptions')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});
