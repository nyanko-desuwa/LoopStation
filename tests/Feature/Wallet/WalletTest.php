<?php

use App\Models\Facility;
use App\Models\PointEarned;
use App\Models\User;
use App\Models\UserWallet;
use App\Services\WalletService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
});

it('đăng ký tự tạo ví điểm', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Wallet User',
        'email' => 'wallet-user@example.com',
        'phone' => '0901000001',
        'password' => 'Password1!',
        'password_confirmation' => 'Password1!',
    ]);

    $response->assertCreated();
    $userId = $response->json('user.id') ?? User::query()->where('email', 'wallet-user@example.com')->value('id');

    $this->assertDatabaseHas('user_wallets', [
        'user_id' => $userId,
        'balance' => 0,
    ]);
});

it('user xem được ví của mình', function () {
    $user = User::factory()->create();
    UserWallet::factory()->forUser($user)->withBalance(50)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/wallet')
        ->assertOk()
        ->assertJsonPath('wallet.balance', 50)
        ->assertJsonPath('wallet.user_id', $user->id);
});

it('WalletService earn cộng điểm và ghi history', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);

    $entry = $service->earn($user, 30, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Thưởng test',
    ]);

    expect($entry->points)->toBe(30);
    expect($service->getWallet($user)->fresh()->balance)->toBe(30);

    $this->assertDatabaseHas('point_earned', [
        'wallet_id' => $entry->wallet_id,
        'points' => 30,
        'source_type' => 'manager_adjust',
    ]);
});

it('WalletService spend trừ điểm khi đủ số dư', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);
    $service->earn($user, 100, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'Nạp',
    ]);

    $spent = $service->spend($user, 40, [
        'source_type' => 'manager_adjust',
        'description' => 'Trừ test',
    ]);

    expect($spent->points)->toBe(40);
    expect($service->getWallet($user)->fresh()->balance)->toBe(60);
});

it('chặn spend khi không đủ số dư', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);
    $service->ensureWallet($user);

    $service->spend($user, 10, [
        'source_type' => 'manager_adjust',
        'description' => 'Trừ quá',
    ]);
})->throws(\Illuminate\Validation\ValidationException::class);

it('manager điều chỉnh điểm qua API', function () {
    $home = Facility::factory()->create();
    $manager = User::factory()->manager($home)->create();
    $target = User::factory()->create();
    app(WalletService::class)->ensureWallet($target);

    Sanctum::actingAs($manager);

    $this->postJson('/api/points/adjust', [
        'user_id' => $target->id,
        'points' => 25,
        'direction' => 'credit',
        'description' => 'Thưởng sự kiện',
    ])->assertOk()
        ->assertJsonPath('wallet.balance', 25)
        ->assertJsonPath('entry.type', 'earned')
        ->assertJsonPath('message', __('wallets.messages.adjusted'));

    $this->postJson('/api/points/adjust', [
        'user_id' => $target->id,
        'points' => 10,
        'direction' => 'debit',
        'description' => 'Điều chỉnh sai',
    ])->assertOk()
        ->assertJsonPath('wallet.balance', 15)
        ->assertJsonPath('entry.type', 'spent');
});

it('user không được adjust điểm', function () {
    Sanctum::actingAs(User::factory()->create());
    $target = User::factory()->create();

    $this->postJson('/api/points/adjust', [
        'user_id' => $target->id,
        'points' => 5,
        'direction' => 'credit',
        'description' => 'Hack',
    ])->assertForbidden();
});

it('user xem lịch sử điểm của mình', function () {
    $user = User::factory()->create();
    $service = app(WalletService::class);
    $service->earn($user, 20, [
        'source_type' => PointEarned::SOURCE_MANAGER_ADJUST,
        'description' => 'A',
    ]);
    $service->spend($user, 5, [
        'source_type' => 'manager_adjust',
        'description' => 'B',
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/wallet/history')
        ->assertOk()
        ->assertJsonPath('balance', 15)
        ->assertJsonCount(2, 'data');
});

it('manager xem được ví user khác', function () {
    $home = Facility::factory()->create();
    $manager = User::factory()->manager($home)->create();
    $target = User::factory()->create();
    UserWallet::factory()->forUser($target)->withBalance(88)->create();

    Sanctum::actingAs($manager);

    $this->getJson("/api/wallets/{$target->id}")
        ->assertOk()
        ->assertJsonPath('wallet.balance', 88);
});

it('user khác không xem ví người khác', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    UserWallet::factory()->forUser($b)->withBalance(10)->create();

    Sanctum::actingAs($a);

    $this->getJson("/api/wallets/{$b->id}")
        ->assertForbidden();
});
