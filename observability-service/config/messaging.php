<?php

return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'orders.events'),
        'queue' => env('RABBITMQ_QUEUE', 'observability-service.events'),
        'dead_letter_exchange' => env('RABBITMQ_DEAD_LETTER_EXCHANGE', 'observability-service.dlx'),
        'dead_letter_queue' => env('RABBITMQ_DEAD_LETTER_QUEUE', 'observability-service.events.dlq'),
        'retry_exchange' => env('RABBITMQ_RETRY_EXCHANGE', 'observability-service.retry'),
        'retry_queue' => env('RABBITMQ_RETRY_QUEUE', 'observability-service.events.retry.v2'),
        'retry_base_delay_ms' => (int) env('RABBITMQ_RETRY_BASE_DELAY_MS', 1000),
        'retry_jitter_ms' => (int) env('RABBITMQ_RETRY_JITTER_MS', 250),
        'max_retries' => (int) env('RABBITMQ_MAX_RETRIES', 3),
    ],
];
