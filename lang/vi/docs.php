<?php

return [
    'api' => [
        'title' => 'Loop Station API',
        'description' => <<<'MD'
        REST API cho **Loop Station**.

        - Xác thực dùng **Laravel Sanctum** (Bearer token).
        - Gửi header `Authorization: Bearer <token>` cho các route cần đăng nhập.
        - Toàn bộ endpoint đặt dưới tiền tố `/api`.
        MD,
        'security' => [
            'bearer' => 'Dán access token (Sanctum) để gọi các endpoint cần đăng nhập. Hệ thống tự thêm header `Authorization: Bearer <token>`.',
        ],
        'labels' => [
            'language' => 'Ngôn ngữ',
        ],
    ],
];
