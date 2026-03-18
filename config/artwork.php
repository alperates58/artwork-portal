<?php

return [

    /*
     * Presigned URL geçerlilik süresi (dakika)
     * Tedarikçi indirme linkinin ne kadar süre geçerli olacağı
     */
    'download_ttl' => (int) env('ARTWORK_DOWNLOAD_TTL', 15),

    /*
     * İzin verilen dosya uzantıları
     */
    'allowed_extensions' => [
        'pdf', 'ai', 'eps', 'zip', 'svg',
        'png', 'jpg', 'jpeg', 'tif', 'tiff',
        'psd', 'indd',
    ],

    /*
     * Maksimum dosya boyutu (MB)
     */
    'max_file_size_mb' => (int) env('ARTWORK_MAX_FILE_SIZE_MB', 1200),

    /*
     * Spaces klasör kök yolu
     */
    'storage_prefix' => env('ARTWORK_STORAGE_PREFIX', 'artworks'),

];
