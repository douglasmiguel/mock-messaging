<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OutboxMessage;
use Illuminate\Support\Str;

class OrderEventRecorder
{
    public function record(Order $order, string $eventType): OutboxMessage
    {
        $order->loadMissing(['client', 'items', 'restaurant']);

        return OutboxMessage::create([
            'id' => (string) Str::ulid(),
            'order_id' => $order->id,
            'event_type' => $eventType,
            'event_version' => 1,
            'payload' => [
                'event_id' => (string) Str::ulid(),
                'event_type' => $eventType,
                'event_version' => 1,
                'aggregate_type' => 'order',
                'aggregate_id' => (string) $order->id,
                'aggregate_version' => $order->aggregate_version,
                'occurred_at' => now()->toIso8601String(),
                'data' => [
                    'order' => [
                        'id' => $order->id,
                        'status' => $order->status->value,
                        'delivery_address' => $order->delivery_address,
                        'items' => $order->items->map(fn ($item): array => [
                            'name' => $item->item_name,
                            'quantity' => $item->quantity,
                        ])->all(),
                    ],
                    'client' => [
                        'id' => $order->client_id,
                        'name' => $order->client?->name ?? $order->client_name,
                        'email' => $order->client_email,
                    ],
                    'restaurant' => [
                        'id' => $order->restaurant_id,
                        'name' => $order->restaurant?->name,
                        'email' => $order->restaurant?->email,
                    ],
                    'rider_id' => $order->rider_id,
                ],
            ],
            'occurred_at' => now(),
        ]);
    }
}
