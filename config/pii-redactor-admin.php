<?php

declare(strict_types=1);

return [
    'enabled' => env('PII_REDACTOR_ADMIN_ENABLED', false),

    'route_prefix' => env('PII_REDACTOR_ADMIN_ROUTE_PREFIX', 'pii-redactor-admin'),
    'api_prefix' => env('PII_REDACTOR_ADMIN_API_PREFIX', 'pii-redactor-admin/api'),
    'middleware' => array_filter(array_map('trim', explode(',', env('PII_REDACTOR_ADMIN_MIDDLEWARE', 'web,auth')))),

    'abilities' => [
        'view' => env('PII_REDACTOR_ADMIN_VIEW_ABILITY', 'viewPiiRedactorAdmin'),
        'detokenise' => env('PII_REDACTOR_ADMIN_DETOKENISE_ABILITY', 'detokenisePiiRedactor'),
        'raw_samples' => env('PII_REDACTOR_ADMIN_RAW_SAMPLES_ABILITY', 'viewPiiRedactorRawSamples'),
    ],

    'throttle' => [
        'scan' => env('PII_REDACTOR_ADMIN_SCAN_THROTTLE', '30,1'),
        'redact' => env('PII_REDACTOR_ADMIN_REDACT_THROTTLE', '30,1'),
        'detokenise' => env('PII_REDACTOR_ADMIN_DETOKENISE_THROTTLE', '6,1'),
    ],

    'token_maps' => [
        'per_page' => (int) env('PII_REDACTOR_ADMIN_TOKEN_MAPS_PER_PAGE', 25),
    ],
];
