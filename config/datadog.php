<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datadog APM Configuration
    |--------------------------------------------------------------------------
    */
    
    'enabled' => env('DATADOG_ENABLED', true),
    
    'service_name' => env('DATADOG_SERVICE_NAME', 'pineus-tilu'),
    
    'env' => env('DATADOG_ENV', 'production'),
    
    'version' => env('DATADOG_VERSION', '1.0.0'),
    
    'agent_host' => env('DATADOG_AGENT_HOST', 'localhost'),
    
    'agent_port' => env('DATADOG_TRACE_AGENT_PORT', 8126),
    
    'distributed_tracing' => env('DATADOG_DISTRIBUTED_TRACING', true),
    
    'trace_enabled' => env('DATADOG_TRACE_ENABLED', true),
    
    'logs_injection' => env('DATADOG_LOGS_INJECTION', true),
    
    'profiling_enabled' => env('DATADOG_PROFILING_ENABLED', true),
    
    'tags' => [
        'team' => 'pineus-tilu-dev',
        'project' => 'camping-booking',
    ],
];