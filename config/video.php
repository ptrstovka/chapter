<?php

return [
    'max_upload_size_mb' => (int) env('VIDEO_MAX_UPLOAD_SIZE_MB', 5120),
    'queue_connection' => env('VIDEO_QUEUE_CONNECTION', match (env('QUEUE_CONNECTION', 'database')) {
        'redis' => 'redis-video',
        default => 'database-video',
    }),
    'queue' => 'videos',
    'timeout' => (int) env('VIDEO_JOB_TIMEOUT', 21900),
    'work_directory' => env('VIDEO_WORK_DIRECTORY', storage_path('app/private/video-processing')),
    'bitrates' => [360 => 800, 480 => 1400, 720 => 2500, 1080 => 5000, 1440 => 8000, 2160 => 16000],
    'segment_seconds' => 6,
    'upload_retention_hours' => 24,
];
