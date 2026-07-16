<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RolePermission;
use App\Services\PermissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Seed danh mục quyền + mapping mặc định cho 3 role.
     * Idempotent: dùng updateOrCreate theo code, rồi rebuild role_permissions.
     */
    public function run(): void
    {
        $catalog = $this->catalog();

        foreach ($catalog as $row) {
            Permission::query()->updateOrCreate(
                ['code' => $row['code']],
                [
                    'resource' => $row['resource'],
                    'action' => $row['action'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'is_system' => true,
                ]
            );
        }

        $roleMap = $this->defaultRoleMap();

        DB::transaction(function () use ($roleMap): void {
            // Rebuild mapping mặc định (seed system, created_by = null).
            RolePermission::query()->delete();

            $now = now();
            $rows = [];

            foreach ($roleMap as $role => $codes) {
                $ids = Permission::query()
                    ->whereIn('code', $codes)
                    ->pluck('id')
                    ->all();

                foreach ($ids as $id) {
                    $rows[] = [
                        'role' => $role,
                        'permission_id' => $id,
                        'created_by' => null,
                        'created_at' => $now,
                    ];
                }
            }

            if ($rows !== []) {
                // Chunk để tránh packet quá lớn khi seed full catalog.
                foreach (array_chunk($rows, 200) as $chunk) {
                    RolePermission::query()->insert($chunk);
                }
            }
        });

        app(PermissionService::class)->flushAllRoleCaches();
    }

    /**
     * @return list<array{code: string, resource: string, action: string, name: string, description: string}>
     */
    private function catalog(): array
    {
        // Catalog seed: Auth + Facilities + RBAC + Catalogs + Handover + Events + Wallets + Redemptions + Contents + Stickers.
        // Domain sau (sticker redeem, minigame, ...) bổ sung khi triển khai.
        $items = [
            // auth
            ['auth', 'login', 'Đăng nhập', 'Đăng nhập hệ thống'],
            ['auth', 'logout', 'Đăng xuất', 'Đăng xuất phiên hiện tại'],
            ['auth', 'change_password', 'Đổi mật khẩu', 'Đổi mật khẩu tài khoản'],
            ['auth', 'request_password_reset', 'Yêu cầu reset mật khẩu', 'Gửi yêu cầu đặt lại mật khẩu'],
            ['auth', 'reset_password', 'Reset mật khẩu', 'Đặt lại mật khẩu bằng link'],
            ['auth', 'verify_email', 'Xác minh email', 'Xác minh email qua link'],
            // user
            ['user', 'view_own', 'Xem hồ sơ của tôi', 'Xem thông tin tài khoản của chính mình'],
            ['user', 'edit_own', 'Sửa hồ sơ của tôi', 'Sửa thông tin cá nhân của chính mình'],
            ['user', 'view', 'Xem user', 'Xem danh sách/tài khoản user'],
            ['user', 'create', 'Tạo user', 'Tạo tài khoản mới'],
            ['user', 'update', 'Cập nhật user', 'Sửa thông tin tài khoản'],
            ['user', 'delete', 'Xóa user', 'Xóa mềm/xóa tài khoản'],
            ['user', 'lock', 'Khóa user', 'Khóa tài khoản'],
            ['user', 'unlock', 'Mở khóa user', 'Mở khóa tài khoản'],
            ['user', 'assign_role', 'Gán role', 'Gán vai trò cho user'],
            ['user', 'assign_facility', 'Gán cơ sở', 'Gán cơ sở cho staff/manager'],
            // facility
            ['facility', 'view', 'Xem cơ sở', 'Xem danh sách cơ sở'],
            ['facility', 'create', 'Tạo cơ sở', 'Tạo cơ sở mới'],
            ['facility', 'update', 'Cập nhật cơ sở', 'Sửa thông tin cơ sở'],
            ['facility', 'delete', 'Xóa cơ sở', 'Xóa mềm cơ sở'],
            ['facility', 'lock', 'Khóa cơ sở', 'Khóa hiển thị cơ sở'],
            ['facility', 'unlock', 'Mở khóa cơ sở', 'Mở khóa hiển thị cơ sở'],
            // measurement_unit
            ['measurement_unit', 'view', 'Xem đơn vị đo', 'Xem danh sách đơn vị đo'],
            ['measurement_unit', 'create', 'Tạo đơn vị đo', 'Thêm đơn vị đo mới'],
            ['measurement_unit', 'update', 'Cập nhật đơn vị đo', 'Sửa đơn vị đo'],
            ['measurement_unit', 'delete', 'Xóa đơn vị đo', 'Xóa mềm đơn vị đo'],
            // waste_type
            ['waste_type', 'view', 'Xem loại rác', 'Xem danh sách loại rác (full, kể cả custom người khác)'],
            ['waste_type', 'create', 'Tạo loại rác chuẩn', 'Thêm loại rác hệ thống'],
            ['waste_type', 'update', 'Cập nhật loại rác', 'Sửa loại rác'],
            ['waste_type', 'delete', 'Xóa loại rác', 'Xóa mềm loại rác'],
            ['waste_type', 'create_custom', 'Tự thêm loại rác', 'User thêm loại rác riêng cho mình'],
            // handover
            ['handover', 'view', 'Xem đơn chuyển giao', 'Xem đơn theo cơ sở'],
            ['handover', 'view_own', 'Xem đơn của tôi', 'Xem đơn do chính mình tạo'],
            ['handover', 'create', 'Tạo đơn chuyển giao', 'Tạo đơn mới'],
            ['handover', 'update', 'Cập nhật đơn chuyển giao', 'Sửa đơn pending của mình'],
            ['handover', 'cancel', 'Hủy đơn chuyển giao', 'Hủy đơn'],
            ['handover', 'approve', 'Duyệt đơn chuyển giao', 'Duyệt đơn pending'],
            ['handover', 'reject', 'Từ chối đơn chuyển giao', 'Từ chối đơn'],
            ['handover', 'assign_staff', 'Phân công staff', 'Gán staff xử lý đơn'],
            ['handover', 'reschedule', 'Dời lịch đơn', 'Dời lịch hẹn'],
            ['handover', 'complete', 'Hoàn tất đơn', 'Đánh dấu hoàn tất'],
            ['handover', 'record_weight', 'Ghi cân', 'Ghi nhận cân thực tế'],
            ['handover', 'view_logs', 'Xem log đơn', 'Xem lịch sử/thao tác đơn'],
            // event
            ['event', 'view', 'Xem sự kiện', 'Xem danh sách sự kiện'],
            ['event', 'create', 'Tạo sự kiện', 'Tạo sự kiện mới'],
            ['event', 'update', 'Cập nhật sự kiện', 'Sửa sự kiện'],
            ['event', 'delete', 'Xóa sự kiện', 'Xóa mềm sự kiện'],
            ['event', 'publish', 'Xuất bản sự kiện', 'Kích hoạt sự kiện (active)'],
            ['event', 'end', 'Kết thúc sự kiện', 'Đóng sự kiện'],
            ['event', 'assign_staff', 'Phân công staff sự kiện', 'Gán staff vào sự kiện'],
            ['event', 'manage_rewards', 'Quản lý quà sự kiện', 'Quản lý quà minigame'],
            ['event', 'check_in_user', 'Check-in user', 'Điểm danh user tại sự kiện'],
            ['event', 'unlock_minigame', 'Mở khóa minigame', 'Mở minigame sau điều kiện'],
            // event_registration
            ['event_registration', 'view', 'Xem đăng ký sự kiện', 'Xem danh sách đăng ký'],
            ['event_registration', 'create', 'Tạo đăng ký sự kiện', 'User tự đăng ký'],
            ['event_registration', 'cancel', 'Hủy đăng ký', 'Hủy đăng ký sự kiện'],
            ['event_registration', 'check_in', 'Check-in đăng ký', 'Staff check-in giúp'],
            ['event_registration', 'mark_absent', 'Đánh dấu vắng mặt', 'Đánh dấu absent'],
            ['event_registration', 'view_own', 'Xem đăng ký của tôi', 'Xem đăng ký của chính mình'],
            // wallet / points
            ['wallet', 'view', 'Xem ví', 'Xem ví điểm của user'],
            ['wallet', 'view_own', 'Xem ví của tôi', 'Xem ví của chính mình'],
            ['points', 'view_own_history', 'Xem lịch sử điểm của tôi', 'Xem giao dịch điểm của chính mình'],
            ['points', 'adjust', 'Điều chỉnh điểm', 'Điều chỉnh cộng/trừ điểm'],
            // reward catalog / redemptions
            ['reward_catalog', 'view', 'Xem danh mục quà', 'Xem danh mục quà đổi điểm'],
            ['reward_catalog', 'create', 'Tạo quà', 'Thêm quà vào danh mục'],
            ['reward_catalog', 'update', 'Cập nhật quà', 'Sửa quà đổi'],
            ['reward_catalog', 'delete', 'Xóa quà', 'Xóa mềm quà'],
            ['redemption', 'view', 'Xem đổi quà', 'Xem tất cả đơn đổi quà'],
            ['redemption', 'view_own', 'Xem đổi quà của tôi', 'Xem đơn đổi quà của chính mình'],
            ['redemption', 'create', 'Tạo đổi quà', 'Đổi quà bằng điểm'],
            ['redemption', 'cancel', 'Hủy đổi quà', 'Hủy đổi quà'],
            ['redemption', 'fulfill', 'Xác nhận giao quà', 'Xác nhận đã giao quà'],
            // educational content
            ['content', 'view', 'Xem bài học (full)', 'Xem tất cả bài học kể cả pending'],
            ['content', 'create', 'Tạo bài học', 'Staff/manager soạn bài học'],
            ['content', 'update', 'Cập nhật bài học', 'Sửa bài học chưa publish'],
            ['content', 'delete', 'Xóa bài học', 'Xóa mềm bài học'],
            ['content', 'approve', 'Duyệt bài học', 'Manager duyệt/từ chối bài'],
            ['content', 'read', 'Đọc bài học', 'User bắt đầu/hoàn tất đọc bài'],
            // sticker sets / stickers (core inventory + drop; redeem vật lý phase sau)
            ['sticker_set', 'view', 'Xem bộ sticker', 'Xem danh sách bộ sticker'],
            ['sticker_set', 'create', 'Tạo bộ sticker', 'Thêm bộ sticker mới'],
            ['sticker_set', 'update', 'Cập nhật bộ sticker', 'Sửa bộ sticker'],
            ['sticker_set', 'delete', 'Xóa bộ sticker', 'Xóa mềm bộ sticker'],
            ['sticker', 'view', 'Xem sticker', 'Xem catalog + inventory user khác'],
            ['sticker', 'create', 'Tạo sticker', 'Thêm sticker vào bộ'],
            ['sticker', 'update', 'Cập nhật sticker', 'Sửa sticker'],
            ['sticker', 'delete', 'Xóa sticker', 'Xóa mềm sticker'],
            ['sticker', 'obtain', 'Nhận sticker', 'Nhận sticker từ drop (user)'],
            ['sticker', 'redeem', 'Đổi sticker vật lý', 'User đổi sticker ảo lấy vật phẩm'],
            // sticker reward items / rules / redemptions (đổi sticker vật lý)
            ['sticker_reward_item', 'view', 'Xem vật phẩm quà sticker', 'Xem danh mục vật phẩm đổi sticker'],
            ['sticker_reward_item', 'create', 'Tạo vật phẩm quà sticker', 'Thêm vật phẩm đổi sticker'],
            ['sticker_reward_item', 'update', 'Cập nhật vật phẩm quà sticker', 'Sửa vật phẩm đổi sticker'],
            ['sticker_reward_item', 'delete', 'Xóa vật phẩm quà sticker', 'Xóa mềm vật phẩm đổi sticker'],
            ['sticker_reward_item', 'adjust_stock', 'Chỉnh tồn kho vật phẩm', 'Điều chỉnh số lượng còn lại trong kho'],
            ['sticker_reward_rule', 'view', 'Xem rule bó quà', 'Xem cấu hình 1 sticker ra vật phẩm nào'],
            ['sticker_reward_rule', 'create', 'Tạo rule bó quà', 'Thêm cấu hình bó quà'],
            ['sticker_reward_rule', 'update', 'Cập nhật rule bó quà', 'Sửa cấu hình bó quà'],
            ['sticker_reward_rule', 'delete', 'Xóa rule bó quà', 'Xóa cấu hình bó quà'],
            ['sticker_redemption', 'view', 'Xem đơn đổi sticker', 'Xem tất cả đơn đổi sticker vật lý'],
            ['sticker_redemption', 'fulfill', 'Xử lý đơn đổi sticker', 'Xác nhận giao/đóng gói đơn đổi sticker'],
            ['sticker_redemption', 'cancel', 'Hủy đơn đổi sticker', 'Hủy + hoàn sticker cho user'],
            // permission / role_permission
            ['permission', 'view', 'Xem quyền', 'Xem danh mục quyền'],
            ['permission', 'create', 'Tạo quyền', 'Thêm quyền mới'],
            ['permission', 'update', 'Cập nhật quyền', 'Sửa quyền'],
            ['permission', 'delete', 'Xóa quyền', 'Xóa quyền'],
            ['role_permission', 'view', 'Xem mapping role', 'Xem quyền theo role'],
            ['role_permission', 'update', 'Cập nhật mapping role', 'Sửa quyền theo role'],
        ];

        return array_map(static function (array $row): array {
            [$resource, $action, $name, $description] = $row;

            return [
                'code' => "{$resource}.{$action}",
                'resource' => $resource,
                'action' => $action,
                'name' => $name,
                'description' => $description,
            ];
        }, $items);
    }

    /**
     * @return array<string, list<string>>
     */
    private function defaultRoleMap(): array
    {
        $user = [
            'auth.login', 'auth.logout', 'auth.change_password',
            'auth.request_password_reset', 'auth.reset_password', 'auth.verify_email',
            'user.view_own', 'user.edit_own',
            'facility.view',
            'measurement_unit.view',
            'waste_type.view', 'waste_type.create_custom',
            'handover.view_own', 'handover.create', 'handover.update', 'handover.cancel',
            'handover.reschedule', 'handover.view_logs',
            'event.view',
            'event_registration.create', 'event_registration.cancel', 'event_registration.view_own',
            'wallet.view_own', 'points.view_own_history',
            'reward_catalog.view',
            'redemption.view_own', 'redemption.create', 'redemption.cancel',
            'content.read',
            'sticker_set.view', 'sticker.view', 'sticker.obtain', 'sticker.redeem',
            'sticker_reward_item.view', 'sticker_reward_rule.view',
        ];

        $staff = array_values(array_unique(array_merge($user, [
            'user.view', 'user.update',
            'handover.view', 'handover.approve', 'handover.reject', 'handover.assign_staff',
            'handover.complete', 'handover.record_weight',
            'event_registration.view', 'event_registration.check_in', 'event_registration.mark_absent',
            'event.check_in_user', 'event.unlock_minigame',
            'wallet.view',
            'redemption.view', 'redemption.fulfill',
            'content.view', 'content.create', 'content.update',
            'sticker_reward_item.adjust_stock',
            'sticker_redemption.view', 'sticker_redemption.fulfill', 'sticker_redemption.cancel',
        ])));

        $manager = array_values(array_unique(array_merge($staff, [
            'user.create', 'user.delete', 'user.lock', 'user.unlock',
            'user.assign_role', 'user.assign_facility',
            'facility.create', 'facility.update', 'facility.delete',
            'facility.lock', 'facility.unlock',
            'measurement_unit.create', 'measurement_unit.update', 'measurement_unit.delete',
            'waste_type.create', 'waste_type.update', 'waste_type.delete',
            'event.create', 'event.update', 'event.delete', 'event.publish', 'event.end',
            'event.assign_staff', 'event.manage_rewards',
            'points.adjust',
            'reward_catalog.create', 'reward_catalog.update', 'reward_catalog.delete',
            'content.delete', 'content.approve',
            'sticker_set.create', 'sticker_set.update', 'sticker_set.delete',
            'sticker.create', 'sticker.update', 'sticker.delete',
            'sticker_reward_item.create', 'sticker_reward_item.update', 'sticker_reward_item.delete',
            'sticker_reward_rule.create', 'sticker_reward_rule.update', 'sticker_reward_rule.delete',
            'permission.view', 'permission.create', 'permission.update', 'permission.delete',
            'role_permission.view', 'role_permission.update',
        ])));

        return [
            'user' => $user,
            'staff' => $staff,
            'manager' => $manager,
        ];
    }
}
