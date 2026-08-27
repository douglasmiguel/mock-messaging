<?php

return [
    'rabbitmq' => [
        'host' => env('RABBITMQ_HOST', '127.0.0.1'),
        'port' => (int) env('RABBITMQ_PORT', 5672),
        'user' => env('RABBITMQ_USER', 'guest'),
        'password' => env('RABBITMQ_PASSWORD', 'guest'),
        'vhost' => env('RABBITMQ_VHOST', '/'),
        'exchange' => env('RABBITMQ_EXCHANGE', 'orders.events'),
    ],
    'topology' => [
        'notification' => [
            'queue' => env('NOTIFICATION_RABBITMQ_QUEUE', 'notification-service.orders'),
            'dead_letter_exchange' => env('NOTIFICATION_RABBITMQ_DEAD_LETTER_EXCHANGE', 'notification-service.dlx'),
            'dead_letter_queue' => env('NOTIFICATION_RABBITMQ_DEAD_LETTER_QUEUE', 'notification-service.orders.dlq'),
            'retry_exchange' => env('NOTIFICATION_RABBITMQ_RETRY_EXCHANGE', 'notification-service.retry'),
            'retry_queue' => env('NOTIFICATION_RABBITMQ_RETRY_QUEUE', 'notification-service.orders.retry.v2'),
            'bindings' => ['order.*'],
        ],
        'rider' => [
            'queue' => env('RIDER_RABBITMQ_QUEUE', 'rider-service.orders'),
            'dead_letter_exchange' => env('RIDER_RABBITMQ_DEAD_LETTER_EXCHANGE', 'rider-service.dlx'),
            'dead_letter_queue' => env('RIDER_RABBITMQ_DEAD_LETTER_QUEUE', 'rider-service.orders.dlq'),
            'retry_exchange' => env('RIDER_RABBITMQ_RETRY_EXCHANGE', 'rider-service.retry'),
            'retry_queue' => env('RIDER_RABBITMQ_RETRY_QUEUE', 'rider-service.orders.retry.v2'),
            'bindings' => ['order.ready_for_pickup', 'order.delivered'],
        ],
        'observability' => [
            'queue' => env('OBSERVABILITY_RABBITMQ_QUEUE', 'observability-service.events'),
            'dead_letter_exchange' => env('OBSERVABILITY_RABBITMQ_DEAD_LETTER_EXCHANGE', 'observability-service.dlx'),
            'dead_letter_queue' => env('OBSERVABILITY_RABBITMQ_DEAD_LETTER_QUEUE', 'observability-service.events.dlq'),
            'retry_exchange' => env('OBSERVABILITY_RABBITMQ_RETRY_EXCHANGE', 'observability-service.retry'),
            'retry_queue' => env('OBSERVABILITY_RABBITMQ_RETRY_QUEUE', 'observability-service.events.retry.v2'),
            'bindings' => ['#'],
        ],
    ],
    'service_key' => env('LOCAL_SERVICE_KEY', 'mock-messaging-local-key'),
];
