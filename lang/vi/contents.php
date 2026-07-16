<?php

return [
    'messages' => [
        'created' => 'Tạo bài học thành công.',
        'updated' => 'Cập nhật bài học thành công.',
        'approved' => 'Duyệt bài học thành công.',
        'rejected' => 'Từ chối bài học thành công.',
        'deleted' => 'Xóa bài học thành công.',
        'read_started' => 'Bắt đầu đọc bài học.',
        'read_completed' => 'Hoàn tất đọc bài. Chưa nhận điểm (hết quota hoặc không có thưởng).',
        'read_rewarded' => 'Hoàn tất đọc bài và nhận điểm thành công.',
        'not_editable' => 'Chỉ được sửa bài chưa xuất bản (pending/rejected).',
        'not_pending' => 'Chỉ thao tác được trên bài đang chờ duyệt.',
        'timer_not_reached' => 'Chưa đủ thời gian đọc tối thiểu để hoàn tất.',
        'points_earned_description' => 'Điểm từ đọc bài: :title',
    ],
    'labels' => [
        'title' => 'Tiêu đề',
        'content' => 'Nội dung',
        'thumbnail_url' => 'Ảnh bìa',
        'timer_seconds' => 'Thời gian đọc tối thiểu (giây)',
        'points_reward' => 'Điểm thưởng',
        'status' => 'Trạng thái',
    ],
    'statuses' => [
        'pending' => 'Chờ duyệt',
        'published' => 'Đã xuất bản',
        'rejected' => 'Từ chối',
    ],
];
