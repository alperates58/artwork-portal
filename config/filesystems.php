<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'permissions' => [
                'file' => [
                    'public' => 0664,
                    'private' => 0664,
                ],
                'dir' => [
                    'public' => 0775,
                    'private' => 0775,
                ],
            ],
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'permissions' => [
                'file' => [
                    'public' => 0664,
                    'private' => 0664,
                ],
                'dir' => [
                    'public' => 0775,
                    'private' => 0775,
                ],
            ],
            'throw'      => false,
        ],

        // DigitalOcean Spaces (S3 uyumlu)
        'spaces' => [
            'driver'   => 's3',
            'key'      => env('DO_SPACES_KEY'),
            'secret'   => env('DO_SPACES_SECRET'),
            'endpoint' => env('DO_SPACES_ENDPOINT', 'https://fra1.digitaloceanspaces.com'),
            'region'   => env('DO_SPACES_REGION', 'fra1'),
            'bucket'   => env('DO_SPACES_BUCKET'),
            'url'      => env('DO_SPACES_URL'),
            'visibility'   => 'private',
            'stream_reads' => true,   // büyük dosyalar için — bellek taşmaz
            'throw'        => true,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
