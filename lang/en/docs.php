<?php

return [
    'api' => [
        'title' => 'Loop Station API',
        'description' => <<<'MD'
        REST API for **Loop Station**.

        - Authentication uses **Laravel Sanctum** (Bearer token).
        - Send the `Authorization: Bearer <token>` header for routes that require authentication.
        - All endpoints live under the `/api` prefix.
        MD,
        'security' => [
            'bearer' => 'Paste an access token (Sanctum) to call authenticated endpoints. The `Authorization: Bearer <token>` header is added automatically.',
        ],
        'labels' => [
            'language' => 'Language',
        ],
    ],
];
