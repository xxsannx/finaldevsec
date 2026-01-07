<?php

// Add this to your config/logging.php channels array

return [
    'channels' => [
        // ... existing channels ...
        
        'datadog' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => Monolog\Handler\StreamHandler::class,
            'formatter' => Monolog\Formatter\JsonFormatter::class,
            'with' => [
                'stream' => '/var/log/datadog/laravel.log',
            ],
            'processors' => [
                // Add Datadog correlation
                function ($record) {
                    if (function_exists('dd_trace_generate_id')) {
                        $record['extra']['dd.trace_id'] = dd_trace_peek_span_id();
                        $record['extra']['dd.span_id'] = dd_trace_peek_span_id();
                    }
                    
                    $record['extra']['service'] = config('datadog.service_name', 'pineus-tilu');
                    $record['extra']['env'] = config('datadog.env', 'production');
                    $record['extra']['version'] = config('datadog.version', '1.0.0');
                    
                    if (auth()->check()) {
                        $record['extra']['user_id'] = auth()->id();
                        $record['extra']['user_email'] = auth()->user()->email;
                    }
                    
                    return $record;
                },
            ],
        ],
        
        // Stack with datadog
        'stack_with_datadog' => [
            'driver' => 'stack',
            'channels' => ['single', 'datadog'],
            'ignore_exceptions' => false,
        ],
    ],
];