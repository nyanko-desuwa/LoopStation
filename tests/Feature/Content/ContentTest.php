<?php

use App\Models\ContentRead;
use App\Models\EducationalContent;
use App\Models\Facility;
use App\Models\PointEarned;
use App\Models\User;
use App\Services\WalletService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(PermissionSeeder::class);
    $this->facility = Facility::factory()->create();
});

it('guest chỉ thấy bài published', function () {
    EducationalContent::factory()->count(2)->published()->create();
    EducationalContent::factory()->create(); // pending

    $this->getJson('/api/contents')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('staff tạo bài pending', function () {
    $staff = User::factory()->staff($this->facility)->create();
    Sanctum::actingAs($staff);

    $this->postJson('/api/contents', [
        'title' => 'Bài học rác thải',
        'content' => '<p>Nội dung HTML</p>',
        'timer_seconds' => 60,
        'points_reward' => 15,
    ])->assertCreated()
        ->assertJsonPath('content.status', 'pending')
        ->assertJsonPath('content.author_id', $staff->id)
        ->assertJsonPath('content.points_reward', 15)
        ->assertJsonPath('message', __('contents.messages.created'));
});

it('manager duyệt bài pending → published', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $staff = User::factory()->staff($this->facility)->create();
    $content = EducationalContent::factory()->byAuthor($staff)->create();

    Sanctum::actingAs($manager);

    $this->postJson("/api/contents/{$content->id}/approve")
        ->assertOk()
        ->assertJsonPath('content.status', 'published')
        ->assertJsonPath('content.approved_by_id', $manager->id);
});

it('manager từ chối bài pending → rejected', function () {
    $manager = User::factory()->manager($this->facility)->create();
    $content = EducationalContent::factory()->create();

    Sanctum::actingAs($manager);

    $this->postJson("/api/contents/{$content->id}/reject")
        ->assertOk()
        ->assertJsonPath('content.status', 'rejected');
});

it('user không sửa được bài published', function () {
    $staff = User::factory()->staff($this->facility)->create();
    $content = EducationalContent::factory()->byAuthor($staff)->published()->create();

    Sanctum::actingAs($staff);

    $this->putJson("/api/contents/{$content->id}", [
        'title' => 'Đổi title',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['content']);
});

it('user đọc đủ timer → nhận điểm', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 5,
        'points_reward' => 20,
        'title' => 'Bài thưởng',
    ]);

    Sanctum::actingAs($user);

    $readId = $this->postJson("/api/contents/{$content->id}/reads")
        ->assertCreated()
        ->json('read.id');

    // Giả lập đã đọc đủ timer: lùi started_at.
    ContentRead::query()->whereKey($readId)->update([
        'started_at' => now()->subSeconds(10),
    ]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('rewarded', true)
        ->assertJsonPath('points_awarded', 20)
        ->assertJsonPath('message', __('contents.messages.read_rewarded'));

    expect(app(WalletService::class)->getWallet($user)->fresh()->balance)->toBe(20);

    $this->assertDatabaseHas('point_earned', [
        'source_type' => PointEarned::SOURCE_CONTENT_READ,
        'reference_id' => $readId,
        'points' => 20,
    ]);
});

it('chặn complete khi chưa đủ timer', function () {
    $user = User::factory()->create();
    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 120,
        'points_reward' => 10,
    ]);

    Sanctum::actingAs($user);

    $readId = $this->postJson("/api/contents/{$content->id}/reads")
        ->assertCreated()
        ->json('read.id');

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertStatus(422)
        ->assertJsonValidationErrors(['read']);
});

it('hết per_content_cap thì complete không cộng điểm', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 1,
        'points_reward' => 10,
    ]);

    $today = now()->toDateString();

    // 2 lượt rewarded trước đó trong ngày (cap = 2) - create tường minh.
    foreach (range(1, 2) as $_) {
        ContentRead::query()->create([
            'user_id' => $user->id,
            'content_id' => $content->id,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->subMinutes(5),
            'rewarded' => true,
            'read_date' => $today,
        ]);
    }

    expect(ContentRead::query()
        ->where('user_id', $user->id)
        ->where('content_id', $content->id)
        ->whereDate('read_date', $today)
        ->where('rewarded', true)
        ->count())->toBe(2);

    Sanctum::actingAs($user);

    $readId = $this->postJson("/api/contents/{$content->id}/reads")
        ->assertCreated()
        ->json('read.id');

    ContentRead::query()->whereKey($readId)->update([
        'started_at' => now()->subSeconds(5),
    ]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('rewarded', false)
        ->assertJsonPath('points_awarded', 0)
        ->assertJsonPath('reason', 'content_cap_reached');

    expect(app(WalletService::class)->getWallet($user)->fresh()->balance)->toBe(0);
});

it('complete idempotent: gọi lại không cộng thêm điểm', function () {
    $user = User::factory()->create();
    app(WalletService::class)->ensureWallet($user);

    $content = EducationalContent::factory()->published()->create([
        'timer_seconds' => 1,
        'points_reward' => 12,
    ]);

    Sanctum::actingAs($user);

    $readId = $this->postJson("/api/contents/{$content->id}/reads")
        ->assertCreated()
        ->json('read.id');

    ContentRead::query()->whereKey($readId)->update([
        'started_at' => now()->subSeconds(5),
    ]);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('points_awarded', 12);

    $this->postJson("/api/contents/{$content->id}/reads/{$readId}/complete")
        ->assertOk()
        ->assertJsonPath('rewarded', true)
        ->assertJsonPath('points_awarded', 12);

    expect(app(WalletService::class)->getWallet($user)->fresh()->balance)->toBe(12);
    expect(PointEarned::query()->where('source_type', PointEarned::SOURCE_CONTENT_READ)->count())->toBe(1);
});

it('user không thấy bài pending của người khác', function () {
    $content = EducationalContent::factory()->create(); // pending
    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/contents/{$content->id}")
        ->assertNotFound();
});
