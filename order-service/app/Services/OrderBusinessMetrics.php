<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OutboxMessage;
use App\Models\Restaurant;
use Illuminate\Support\Str;

class OrderBusinessMetrics
{
    public function render(): string
    {
        return implode("\n", [
            ...$this->currentOrdersByStatus(),
            ...$this->currentOrdersByRestaurantAndStatus(),
            ...$this->ordersCreatedByStatus(),
            ...$this->outboxBacklog(),
            '',
        ]);
    }

    /** @return list<string> */
    private function currentOrdersByStatus(): array
    {
        $counts = Order::query()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $lines = [
            '# HELP mock_messaging_order_service_orders_current_by_status Current orders grouped by their current status.',
            '# TYPE mock_messaging_order_service_orders_current_by_status gauge',
        ];

        foreach (OrderStatus::cases() as $status) {
            $lines[] = 'mock_messaging_order_service_orders_current_by_status{status="'.$status->value.'"} '.($counts[$status->value] ?? 0);
        }

        return $lines;
    }

    /** @return list<string> */
    private function currentOrdersByRestaurantAndStatus(): array
    {
        $order = new Order;
        $restaurant = new Restaurant;

        $orders = Order::query()
            ->toBase()
            ->join($restaurant->getTable(), $order->qualifyColumn('restaurant_id'), '=', $restaurant->qualifyColumn('id'))
            ->select([
                $order->qualifyColumn('status'),
                $restaurant->qualifyColumn('id').' as restaurant_id',
                $restaurant->qualifyColumn('name').' as restaurant_name',
            ])
            ->selectRaw('count(*) as total')
            ->groupBy($order->qualifyColumn('status'), $restaurant->qualifyColumn('id'), $restaurant->qualifyColumn('name'))
            ->orderBy($restaurant->qualifyColumn('name'))
            ->orderBy($order->qualifyColumn('status'))
            ->get();

        $lines = [
            '# HELP mock_messaging_order_service_orders_current_by_restaurant_status Current orders grouped by restaurant and current status.',
            '# TYPE mock_messaging_order_service_orders_current_by_restaurant_status gauge',
        ];

        foreach ($orders as $row) {
            $lines[] = 'mock_messaging_order_service_orders_current_by_restaurant_status{restaurant_id="'.$row->restaurant_id.'",restaurant_name="'.$this->escapeLabel($row->restaurant_name).'",status="'.$this->escapeLabel($row->status).'"} '.$row->total;
        }

        return $lines;
    }

    /** @return list<string> */
    private function ordersCreatedByStatus(): array
    {
        $orders = Order::query()
            ->toBase()
            ->select('status')
            ->selectRaw('count(*) as total')
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $lines = [
            '# HELP mock_messaging_order_service_orders_created_by_status Total orders created, grouped by their current status.',
            '# TYPE mock_messaging_order_service_orders_created_by_status gauge',
        ];

        foreach ($orders as $row) {
            $lines[] = 'mock_messaging_order_service_orders_created_by_status{status="'.$this->escapeLabel($row->status).'"} '.$row->total;
        }

        return $lines;
    }

    /** @return list<string> */
    private function outboxBacklog(): array
    {
        $pending = OutboxMessage::query()->whereNull('published_at')->get(['occurred_at']);
        $oldestAge = $pending->min('occurred_at');
        $failedPublishes = OutboxMessage::query()->sum('publish_attempts');

        return [
            '# HELP mock_messaging_order_service_outbox_pending_messages Unpublished transactional outbox messages.',
            '# TYPE mock_messaging_order_service_outbox_pending_messages gauge',
            'mock_messaging_order_service_outbox_pending_messages '.$pending->count(),
            '# HELP mock_messaging_order_service_outbox_oldest_pending_age_seconds Age of the oldest unpublished outbox message.',
            '# TYPE mock_messaging_order_service_outbox_oldest_pending_age_seconds gauge',
            'mock_messaging_order_service_outbox_oldest_pending_age_seconds '.($oldestAge === null ? 0 : now()->diffInSeconds($oldestAge)),
            '# HELP mock_messaging_order_service_outbox_publish_failures_total Recorded failed publish attempts.',
            '# TYPE mock_messaging_order_service_outbox_publish_failures_total counter',
            'mock_messaging_order_service_outbox_publish_failures_total '.$failedPublishes,
        ];
    }

    private function escapeLabel(string $value): string
    {
        return Str::replace(['\\', '"', "\n"], ['\\\\', '\\"', '\\n'], $value);
    }
}
