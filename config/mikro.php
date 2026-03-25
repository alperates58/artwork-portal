<?php

$legacyBaseUrl = env('MIKRO_ERP_URL', '');
$legacyApiKey = env('MIKRO_ERP_KEY', '');

return [
    'enabled' => filter_var(
        env('MIKRO_ENABLED', filled(env('MIKRO_BASE_URL', $legacyBaseUrl))),
        FILTER_VALIDATE_BOOL
    ),
    'base_url' => env('MIKRO_BASE_URL', $legacyBaseUrl),
    'api_key' => env('MIKRO_API_KEY', $legacyApiKey),
    'username' => env('MIKRO_USERNAME', ''),
    'password' => env('MIKRO_PASSWORD', ''),
    'company_code' => env('MIKRO_COMPANY_CODE', ''),
    'work_year' => env('MIKRO_WORK_YEAR', ''),
    'timeout' => (int) env('MIKRO_TIMEOUT', 30),
    'verify_ssl' => filter_var(env('MIKRO_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
    'shipment_endpoint' => env('MIKRO_SHIPMENT_ENDPOINT', ''),
    'sync_interval_minutes' => (int) env('MIKRO_SYNC_INTERVAL', 5),
    'view_contract' => [
        'name' => 'supplier_order_view_v1',
        'description' => 'ERP-side sabit SQL VIEW alias kontrati',
        'aliases' => [
            'order_no',
            'line_no',
            'stock_code',
            'stock_name',
            'order_qty',
            'supplier_code',
            'supplier_name',
            'order_date',
        ],
        'identity_rules' => [
            'line_no' => 'Gercek ERP satir kimligi sip_satirno olmalidir.',
            'supplier_code' => 'supplier_mikro_accounts.mikro_cari_kod ile eslesir.',
            'supplier_name' => 'Yalniz gosterim amaclidir.',
        ],
    ],
];
