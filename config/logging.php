<?php

// Mendefinisikan variabel lokal untuk path log
// Ini menghilangkan duplikasi literal 'logs/laravel.log'
$log_path = storage_path('logs/laravel.log');

return [
    /*
    |--------------------------------------------------------------------------
    | Logging Channels
    |--------------------------------------------------------------------------
    |
    | Here you may configure the log channels your application uses.
    |
    */

    'channels' => [
        // ... channel configurations lainnya

        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'daily'], // Menggunakan channel single dan daily
            'ignore_exceptions' => false,
        ],

        'single' => [
            'driver' => 'single',
            'path' => $log_path, // Menggunakan variabel $log_path
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => $log_path, // Menggunakan variabel $log_path
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
            'replace_placeholders' => true,
        ],

        // ... channel configurations lainnya
    ],
];