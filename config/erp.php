<?php

return [
    'mikro' => [
        'base_url' => env('MIKRO_ERP_URL', ''),
        'api_key' => env('MIKRO_ERP_KEY', ''),
        'shipment_endpoint' => env('MIKRO_SHIPMENT_ENDPOINT', ''),
        'use_direct_db' => env('MIKRO_USE_DIRECT_DB', false),
        'sync_interval_minutes' => env('MIKRO_SYNC_INTERVAL', 60),
    ],
];
