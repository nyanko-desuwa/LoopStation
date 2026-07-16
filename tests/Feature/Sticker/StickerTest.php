<?php

use App\Models\ContentRead;
use App\Models\EducationalContent;
use App\Models\Facility;
use App\Models\PointEarned;
use App\Models\Sticker;
use App\Models\StickerObtainLog;
use App\Models\StickerSet;
use App\Models\User;
use App\Models\UserSticker;
use App\Services\WalletService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->facility = Facility::factory()->create();
});

it('guest chỉ thấy bộ sticker active', function () {
    StickerSet::factory()->count(2)->create();
    StickerSet::factory()->locked()->create();

    $this->getJson('/api/sticker-sets')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('manager tạo bộ + sticker', function () {
    $manager = User::factory()->manager($this->facility)->create();
    Sanctum::actingAs($manager);

    $setId = $this->postJson('/api/sticker-sets', [
        'name' => 'Đại dương',
        'theme' => 'biển',
    ])->assertCreated()
        ->assertJsonPath('sticker_set.name', 'Đại dương')
        ->json('sticker_set.id');

    $this->postJson('/api/stickers', [
        'set_id' => $setId,
        'name' => 'Cá heo',
        'image_url' => '/storage/stickers/dolphin.png',
        'rarity' => 'rare',
        'drop_weight' => 5,
        'bonus_points' => 15,
    ])->assertCreated()
        ->assertJsonPath('sticker.name', 'Cá heo')
        ->assertJsonPath('sticker.rarity', 'rare')
        ->assertJsonPath('sticker.bonus_points', 15);
});

it('user không tạo được bộ sticker', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/sticker-sets', [
        'name' => 'Hack set',
    ])->assertForbidden();
});

it('đọc bài gắn sticker set → nhận sticker + inventory', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $set = StickerSet::factory()->create();
    $sticker = Sticker::factory()->inSet($set)->create([
        'name' => 'Lá xanh',
        'drop_weight' => 10,
        'bonus_points' => 0,
    ]);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 5,
        'points_reward' => 10,
        'sticker_set_id' => $set->id,
    ]);

    Sanctum::actingAs($user);

    $readId = $this->postJson("/api/contents/{$content->id}/reads")
        ->assertCreated()
        ->json('read.id');

    ContentRead::query()->whereKey($readId)->update([
        'started_at' => now()->subSeconds(10),
    ]);

    $response = $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('rewarded', true)
        ->assertJsonPath('points_awarded', 10)
        ->assertJsonPath('sticker_drop.sticker.id', $sticker->id)
        ->assertJsonPath('sticker_drop.first_owned', true);

    expect($response->json('sticker_drop.sticker.name'))->toBe('Lá xanh');

    $this->assertDatabaseHas('user_stickers', [
        'user_id' => $user->id,
        'sticker_id' => $sticker->id,
        'quantity' => 1,
        'total_obtained' => 1,
    ]);

    $this->assertDatabaseHas('sticker_obtain_logs', [
        'user_id' => $user->id,
        'sticker_id' => $sticker->id,
        'source_content_id' => $content->id,
    ]);

    $this->getJson('/api/my/stickers')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sticker_id', $sticker->id);
});

it('lần đầu sở hữu sticker → cộng bonus_points sticker_bonus', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $set = StickerSet::factory()->create();
    Sticker::factory()->inSet($set)->create([
        'drop_weight' => 1,
        'bonus_points' => 25,
    ]);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 1,
        'points_reward' => 5,
        'sticker_set_id' => $set->id,
    ]);

    Sanctum::actingAs($user);

    $readId = $this->postJson("/api/contents/{$content->id}/reads")->json('read.id');
    ContentRead::query()->whereKey($readId)->update(['started_at' => now()->subSeconds(5)]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('sticker_drop.first_owned', true)
        ->assertJsonPath('sticker_drop.bonus_points', 25);

    // 5 content_read + 25 sticker_bonus
    expect(app(WalletService::class)->getWallet($user)->fresh()->balance)->toBe(30);

    $this->assertDatabaseHas('point_earned', [
        'source_type' => PointEarned::SOURCE_STICKER_BONUS,
        'points' => 25,
    ]);
});

it('nhận trùng sticker không cộng bonus lần 2', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $set = StickerSet::factory()->create();
    $sticker = Sticker::factory()->inSet($set)->create([
        'drop_weight' => 1,
        'bonus_points' => 40,
    ]);

    // Seed đã sở hữu trước.
    UserSticker::create([
        'user_id' => $user->id,
        'sticker_id' => $sticker->id,
        'quantity' => 1,
        'total_obtained' => 1,
        'first_obtained_at' => now()->subDay(),
        'last_obtained_at' => now()->subDay(),
    ]);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 1,
        'points_reward' => 0,
        'sticker_set_id' => $set->id,
    ]);

    Sanctum::actingAs($user);
    $readId = $this->postJson("/api/contents/{$content->id}/reads")->json('read.id');
    ContentRead::query()->whereKey($readId)->update(['started_at' => now()->subSeconds(5)]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('sticker_drop.first_owned', false)
        ->assertJsonPath('sticker_drop.bonus_points', 0)
        ->assertJsonPath('sticker_drop.sticker.id', $sticker->id);

    expect(UserSticker::query()->where('user_id', $user->id)->where('sticker_id', $sticker->id)->value('quantity'))
        ->toBe(2);
    expect(UserSticker::query()->where('user_id', $user->id)->where('sticker_id', $sticker->id)->value('total_obtained'))
        ->toBe(2);

    expect(PointEarned::query()->where('source_type', PointEarned::SOURCE_STICKER_BONUS)->count())->toBe(0);
});

it('bộ locked không drop sticker', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $set = StickerSet::factory()->locked()->create();
    Sticker::factory()->inSet($set)->create(['drop_weight' => 1]);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 1,
        'points_reward' => 8,
        'sticker_set_id' => $set->id,
    ]);

    Sanctum::actingAs($user);
    $readId = $this->postJson("/api/contents/{$content->id}/reads")->json('read.id');
    ContentRead::query()->whereKey($readId)->update(['started_at' => now()->subSeconds(5)]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('rewarded', true)
        ->assertJsonPath('points_awarded', 8)
        ->assertJsonPath('sticker_drop', null);

    expect(StickerObtainLog::query()->count())->toBe(0);
});

it('complete lần 2 không drop sticker lại', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $set = StickerSet::factory()->create();
    Sticker::factory()->inSet($set)->create(['drop_weight' => 1, 'bonus_points' => 0]);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 1,
        'points_reward' => 3,
        'sticker_set_id' => $set->id,
    ]);

    Sanctum::actingAs($user);
    $readId = $this->postJson("/api/contents/{$content->id}/reads")->json('read.id');
    ContentRead::query()->whereKey($readId)->update(['started_at' => now()->subSeconds(5)]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")->assertOk();
    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('sticker_drop', null);

    expect(StickerObtainLog::query()->where('user_id', $user->id)->count())->toBe(1);
});
