<?php

return [
    'mikro' => [
        /*
         * Mikro ERP REST API bağlantı bilgileri
         * Boş bırakılırsa mock veri kullanılır (geliştirme)
         */
        'base_url' => env('MIKRO_ERP_URL', ''),
        'api_key'  => env('MIKRO_ERP_KEY', ''),

        /*
         * Alternatif: Mikro veritabanına doğrudan bağlantı
         * config/database.php içinde 'mikro' connection tanımlanmalı
         */
        'use_direct_db' => env('MIKRO_USE_DIRECT_DB', false),

        /*
         * Sync sıklığı (dakika) — scheduler tarafından kullanılır
         */
        'sync_interval_minutes' => env('MIKRO_SYNC_INTERVAL', 60),
    ],
];
