<?php

return [
    'messages' => [
        'created' => 'Đổi quà thành công.',
        'cancelled' => 'Hủy đơn đổi quà thành công. Điểm đã được hoàn.',
        'shipping' => 'Đơn đã chuyển sang trạng thái đang giao.',
        'fulfilled' => 'Xác nhận giao quà thành công.',
        'reward_created' => 'Tạo quà trong danh mục thành công.',
        'reward_updated' => 'Cập nhật quà thành công.',
        'reward_deleted' => 'Xóa quà thành công.',
        'invalid_method' => 'Hình thức nhận quà không hợp lệ.',
        'delivery_fields_required' => 'Đơn ship cần đủ tên, SĐT và địa chỉ người nhận.',
        'invalid_reward' => 'Quà không tồn tại.',
        'reward_locked' => 'Quà đang tạm khóa, không thể đổi.',
        'out_of_stock' => 'Quà không đủ tồn kho.',
        'not_delivery' => 'Chỉ đơn ship mới chuyển sang shipping.',
        'not_pending' => 'Chỉ đơn đang chờ mới thao tác được.',
        'already_closed' => 'Đơn đã đóng.',
        'not_cancellable' => 'Đơn không thể hủy.',
        'spend_description' => 'Đổi quà: :name x:qty',
        'refund_description' => 'Hoàn điểm hủy đơn đổi quà #:id',
    ],
    'labels' => [
        'reward_id' => 'Quà',
        'quantity' => 'Số lượng',
        'fulfillment_method' => 'Hình thức nhận',
        'points_cost' => 'Điểm cần',
        'stock' => 'Tồn kho',
    ],
    'statuses' => [
        'pending' => 'Chờ nhận',
        'shipping' => 'Đang giao',
        'fulfilled' => 'Đã giao',
        'cancelled' => 'Đã hủy',
    ],
    'methods' => [
        'pickup' => 'Nhận tại cơ sở',
        'delivery' => 'Ship tận nhà',
    ],
];
