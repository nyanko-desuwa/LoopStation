<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Handover completion rewards
    |--------------------------------------------------------------------------
    |
    | When staff/manager completes a handover, the owner earns points based on
    | summed weight logs (converted to kg when unit symbol is g/t) times
    | points_per_kg, then multiplied by classification quality.
    |
    */

    'handover' => [
        'points_per_kg' => (int) env('HANDOVER_POINTS_PER_KG', 10),

        // Floor when calculated points would be 0 but weight > 0.
        'min_points' => (int) env('HANDOVER_MIN_POINTS', 1),

        'classification_multipliers' => [
            'cleaned_flattened' => 1.2,
            'cleaned' => 1.0,
            'as_is' => 0.8,
            'mixed' => 0.7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Educational content reading rewards
    |--------------------------------------------------------------------------
    |
    | A read grants points only when the reader stays at least timer_seconds
    | (per content) AND still has daily quota: at most daily_cap rewarded reads
    | per day, and per_content_cap rewarded reads per day for one content.
    |
    */

    'content' => [
        // Tối đa lượt rewarded mỗi ngày cho 1 user (mọi bài cộng lại).
        'daily_cap' => (int) env('CONTENT_DAILY_REWARD_CAP', 10),

        // Tối đa lượt rewarded mỗi ngày cho 1 user trên cùng 1 bài.
        'per_content_cap' => (int) env('CONTENT_PER_CONTENT_REWARD_CAP', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Event minigame rewards
    |--------------------------------------------------------------------------
    |
    | When a checked-in user with an unlocked minigame plays, they earn a flat
    | number of points (source event_minigame) once, and may also win one of
    | the event's physical EVENT_REWARDS when stock remains.
    |
    */

    'minigame' => [
        'play_points' => (int) env('MINIGAME_PLAY_POINTS', 20),
    ],

];
