<?php

return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'orders.events'),
        'queue' => env('RABBITMQ_QUEUE', 'rider-service.orders'),
        'dead_letter_exchange' => env('RABBITMQ_DEAD_LETTER_EXCHANGE', 'rider-service.dlx'),
        'dead_letter_queue' => env('RABBITMQ_DEAD_LETTER_QUEUE', 'rider-service.orders.dlq'),
        'retry_exchange' => env('RABBITMQ_RETRY_EXCHANGE', 'rider-service.retry'),
        'retry_queue' => env('RABBITMQ_RETRY_QUEUE', 'rider-service.orders.retry.v2'),
        'retry_base_delay_ms' => (int) env('RABBITMQ_RETRY_BASE_DELAY_MS', 1000),
        'retry_jitter_ms' => (int) env('RABBITMQ_RETRY_JITTER_MS', 250),
        'max_retries' => (int) env('RABBITMQ_MAX_RETRIES', 3),
    ],
    'order_service_url' => env('ORDER_SERVICE_URL', 'https://order-service.test'),
    'service_key' => env('LOCAL_SERVICE_KEY', 'mock-messaging-local-key'),
    'order_service_timeout_seconds' => (int) env('ORDER_SERVICE_TIMEOUT_SECONDS', 3),
    'order_service_connect_timeout_seconds' => (int) env('ORDER_SERVICE_CONNECT_TIMEOUT_SECONDS', 1),
];
