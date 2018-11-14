<?php

return [
    'disk' => env('MEDIA_DISK'),
    'path' => env('MEDIA_PATH'),
    'queue' => env('MEDIA_QUEUE'),

    'temp' => [
        'default' => 'public',

        'disks' => [
            'public' => 'public',
            'private' => 'local',
        ],
    ],
];
