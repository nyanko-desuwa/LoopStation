<?php

use App\Models\Facility;
use App\Models\Sticker;
use App\Models\StickerRedemption;
use App\Models\StickerRewardItem;
use App\Models\StickerRewardRule;
use App\Models\StickerSet;
use App\Models\User;
use App\Models\UserSticker;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->facility = Facility::factory()->create();
});

/**
 * Helper: tạo sticker + item + rule + cho user sẵn inventory.
 *
 * @return array{user: User, sticker: Sticker, item: StickerRewardItem}
 */
function seedRedeemFixture(int $ownedQty = 3, int $required = 2, int $ruleQty = 1, int $stock = 10): array
{
    $user = User::factory()->create();
    $set = StickerSet::factory()->create();
    $sticker = Sticker::factory()->inSet($set)->create([
        'redeem_quantity_required' => $required,
    ]);
    $item = StickerRewardItem::factory()->create(['stock' => $stock]);
    StickerRewardRule::factory()->create([
        'sticker_id' => $sticker->id,
        'reward_item_id' => $item->id,
        'quantity' => $ruleQty,
    ]);

    UserSticker::create([
        'user_id' => $user->id,
        'sticker_id' => $sticker->id,
        'quantity' => $ownedQty,
        'total_obtained' => $ownedQty,
        'first_obtained_at' => now(),
        'last_obtained_at' => now(),
    ]);

    return ['user' => $user, 'sticker' => $sticker, 'item' => $item];
}

it('manager tạo vật phẩm quà + rule cho sticker', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $set = StickerSet::factory()->create();
    $sticker = Sticker::factory()->inSet($set)->create();

    Sanctum::actingAs($manager);

    $itemId = $this->postJson('/api/sticker-reward-items', [
        'name' => 'Kẹo dẻo',
        'stock' => 20,
    ])->assertCreated()
        ->assertJsonPath('reward_item.name', 'Kẹo dẻo')
        ->json('reward_item.id');

    $this->postJson("/api/stickers/{$sticker->id}/reward-rules", [
        'reward_item_id' => $itemId,
        'quantity' => 2,
    ])->assertCreated()
        ->assertJsonPath('rule.reward_item_id', $itemId)
        ->assertJsonPath('rule.quantity', 2);
});

it('user đổi sticker → trừ inventory + trừ stock item + snapshot', function () {
    ['user' => $user, 'sticker' => $sticker, 'item' => $item] = seedRedeemFixture(
        ownedQty: 3, required: 2, ruleQty: 3, stock: 10
    );

    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->assertCreated()
        ->assertJsonPath('redemption.quantity_used', 2)
        ->assertJsonPath('redemption.status', 'pending')
        ->assertJsonPath('redemption.items.0.quantity', 3);

    // Inventory 3 - 2 = 1.
    expect(UserSticker::query()->where('user_id', $user->id)->where('sticker_id', $sticker->id)->value('quantity'))
        ->toBe(1);
    // Stock 10 - 3 = 7.
    expect($item->fresh()->stock)->toBe(7);

    $this->assertDatabaseHas('sticker_redemption_items', [
        'reward_item_id' => $item->id,
        'item_name' => $item->name,
        'quantity' => 3,
    ]);
});

it('không đủ sticker thì chặn đổi', function () {
    ['user' => $user, 'sticker' => $sticker] = seedRedeemFixture(ownedQty: 1, required: 2);

    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['sticker_id']);
});

it('sticker chưa có rule thì chặn đổi', function () {
    $user = User::factory()->create();
    $set = StickerSet::factory()->create();
    $sticker = Sticker::factory()->inSet($set)->create(['redeem_quantity_required' => 1]);
    UserSticker::create([
        'user_id' => $user->id,
        'sticker_id' => $sticker->id,
        'quantity' => 5,
        'total_obtained' => 5,
        'first_obtained_at' => now(),
        'last_obtained_at' => now(),
    ]);

    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['sticker_id']);
});

it('item hết kho thì chặn đổi', function () {
    ['user' => $user, 'sticker' => $sticker] = seedRedeemFixture(
        ownedQty: 3, required: 1, ruleQty: 5, stock: 2
    );

    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['reward_item']);

    // Không trừ sticker khi fail.
    expect(UserSticker::query()->where('user_id', $user->id)->where('sticker_id', $sticker->id)->value('quantity'))
        ->toBe(3);
});

it('pickup thiếu facility_id thì chặn', function () {
    ['user' => $user, 'sticker' => $sticker] = seedRedeemFixture();

    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['facility_id']);
});

it('hủy đơn → hoàn sticker + hoàn stock', function () {
    ['user' => $user, 'sticker' => $sticker, 'item' => $item] = seedRedeemFixture(
        ownedQty: 3, required: 2, ruleQty: 3, stock: 10
    );

    Sanctum::actingAs($user);

    $redemptionId = $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->json('redemption.id');

    // Sau đổi: sticker 1, stock 7.
    $this->postJson("/api/sticker-redemptions/{$redemptionId}/cancel")
        ->assertOk()
        ->assertJsonPath('redemption.status', 'cancelled');

    // Hoàn: sticker về 3, stock về 10.
    expect(UserSticker::query()->where('user_id', $user->id)->where('sticker_id', $sticker->id)->value('quantity'))
        ->toBe(3);
    expect($item->fresh()->stock)->toBe(10);
});

it('staff fulfill đơn pickup', function () {
    ['user' => $user, 'sticker' => $sticker] = seedRedeemFixture();
    $staff = User::factory()->staff($this->facility)->create();

    Sanctum::actingAs($user);
    $redemptionId = $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->json('redemption.id');

    Sanctum::actingAs($staff);
    $this->postJson("/api/sticker-redemptions/{$redemptionId}/fulfill")
        ->assertOk()
        ->assertJsonPath('redemption.status', 'fulfilled')
        ->assertJsonPath('redemption.staff_id', $staff->id);
});

it('user chỉ xem được đơn của mình', function () {
    ['user' => $owner, 'sticker' => $sticker] = seedRedeemFixture();
    $other = User::factory()->create();

    Sanctum::actingAs($owner);
    $redemptionId = $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'pickup',
        'facility_id' => $this->facility->id,
    ])->json('redemption.id');

    Sanctum::actingAs($other);
    $this->getJson("/api/sticker-redemptions/{$redemptionId}")->assertNotFound();
});

it('delivery cần recipient fields', function () {
    ['user' => $user, 'sticker' => $sticker] = seedRedeemFixture();

    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-redemptions', [
        'sticker_id' => $sticker->id,
        'fulfillment_method' => 'delivery',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['recipient_name', 'recipient_phone', 'shipping_address']);
});
