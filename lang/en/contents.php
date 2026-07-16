<?php

return [
    'messages' => [
        'created' => 'Educational content created successfully.',
        'updated' => 'Educational content updated successfully.',
        'approved' => 'Educational content approved successfully.',
        'rejected' => 'Educational content rejected successfully.',
        'deleted' => 'Educational content deleted successfully.',
        'read_started' => 'Started reading the content.',
        'read_completed' => 'Reading completed. No points awarded (quota reached or no reward).',
        'read_rewarded' => 'Reading completed and points awarded successfully.',
        'not_editable' => 'Only unpublished content (pending/rejected) can be edited.',
        'not_pending' => 'Only pending content can be processed this way.',
        'timer_not_reached' => 'Minimum reading time has not been reached yet.',
        'points_earned_description' => 'Points from reading: :title',
    ],
    'labels' => [
        'title' => 'Title',
        'content' => 'Content',
        'thumbnail_url' => 'Thumbnail',
        'timer_seconds' => 'Minimum reading time (seconds)',
        'points_reward' => 'Points reward',
        'status' => 'Status',
    ],
    'statuses' => [
        'pending' => 'Pending',
        'published' => 'Published',
        'rejected' => 'Rejected',
    ],
];
