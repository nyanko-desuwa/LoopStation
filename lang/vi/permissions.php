<?php

return [
    'messages' => [
        'created' => 'Tạo quyền thành công.',
        'updated' => 'Cập nhật quyền thành công.',
        'deleted' => 'Xóa quyền thành công.',
        'forbidden' => 'Bạn không có quyền thực hiện thao tác này.',
        'system_locked' => 'Không thể xóa quyền hệ thống (is_system = true).',
        'invalid_role' => 'Role không hợp lệ.',
        'invalid_permission_ids' => 'Danh sách permission_ids có id không tồn tại.',
        'role_synced' => 'Cập nhật quyền cho role thành công.',
        'role_mismatch' => 'Role trong body phải khớp với role trên URL.',
    ],
    'labels' => [
        'code' => 'Mã quyền',
        'resource' => 'Đối tượng',
        'action' => 'Hành động',
        'name' => 'Tên hiển thị',
        'description' => 'Mô tả',
        'is_system' => 'Quyền hệ thống',
        'role' => 'Vai trò',
        'permission_ids' => 'Danh sách quyền',
    ],
    'roles' => [
        'user' => 'Người dùng',
        'staff' => 'Nhân viên',
        'manager' => 'Quản lý',
    ],
];
