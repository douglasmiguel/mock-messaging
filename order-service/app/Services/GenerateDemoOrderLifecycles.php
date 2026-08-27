<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Client;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Support\Facades\DB;
use LogicException;

class GenerateDemoOrderLifecycles
{
    public function __construct(private readonly OrderEventRecorder $events) {}

    /** @return array<string, int> */
    public function generate(int $count, int $days): array
    {
        $restaurants = Restaurant::query()
            ->with(['items' => fn ($query) => $query->where('is_available', true)])
            ->whereHas('items', fn ($query) => $query->where('is_available', true))
            ->get();
        $clients = Client::query()->get();

        if ($restaurants->isEmpty() || $clients->isEmpty()) {
            throw new LogicException('Seed at least one client and one restaurant with an available menu item before generating demo orders.');
        }

        $terminalStatuses = collect(OrderStatus::cases())->shuffle()->values();
        $generatedByStatus = array_fill_keys(array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases()), 0);

        for ($index = 0; $index < $count; $index++) {
            $terminalStatus = $terminalStatuses[$index % $terminalStatuses->count()];

            DB::transaction(function () use ($restaurants, $clients, $terminalStatus, $days, $index): void {
                $this->createLifecycle(
                    $restaurants->random(),
                    $clients->random(),
                    $terminalStatus,
                    $days,
                    ($index % 20) + 1,
                );
            });

            $generatedByStatus[$terminalStatus->value]++;
        }

        return $generatedByStatus;
    }

    private function createLifecycle(Restaurant $restaurant, Client $client, OrderStatus $terminalStatus, int $days, int $riderId): void
    {
        $menuItems = $restaurant->items->shuffle()->take(min(2, $restaurant->items->count()));
        $orderItems = $menuItems->map(function ($item): array {
            $quantity = random_int(1, 3);

            return [
                'restaurant_item_id' => $item->id,
                'item_name' => $item->name,
                'item_price' => $item->item_price,
                'quantity' => $quantity,
                'total_price' => $item->item_price * $quantity,
            ];
        });
        $orderPrice = $orderItems->sum('total_price');
        $deliveryFee = config('orders.delivery_fee');
        $createdAt = now()->subMinutes(random_int(1, $days * 24 * 60));

        $order = Order::create([
            'client_id' => $client->id,
            'client_name' => $client->name,
            'client_phone' => $client->phone,
            'client_email' => $client->email,
            'delivery_address' => fake()->address(),
            'restaurant_id' => $restaurant->id,
            'order_price' => $orderPrice,
            'delivery_fee' => $deliveryFee,
            'total_price' => $orderPrice + $deliveryFee,
            'status' => $terminalStatus === OrderStatus::New ? OrderStatus::New : OrderStatus::Placed,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
        $order->items()->createMany($orderItems->all());
        $order = $order->fresh(['client', 'items', 'restaurant']);
        $this->events->record($order, 'order.placed');

        if ($terminalStatus === OrderStatus::New) {
            return;
        }

        if ($terminalStatus === OrderStatus::Placed) {
            return;
        }

        $order = $this->transition($order, OrderStatus::Accepted, 'order.accepted');

        if ($terminalStatus === OrderStatus::Accepted) {
            return;
        }

        if ($terminalStatus === OrderStatus::Preparing) {
            $this->transition($order, OrderStatus::Preparing, 'order.preparing');

            return;
        }

        if ($terminalStatus === OrderStatus::Cancelled) {
            $this->transition($order, OrderStatus::Cancelled, 'order.cancelled');

            return;
        }

        $order = $this->transition($order, OrderStatus::ReadyForPickup, 'order.ready_for_pickup');

        if ($terminalStatus === OrderStatus::ReadyForPickup) {
            return;
        }

        $order = $this->transition($order, OrderStatus::RiderAssigned, 'order.rider_assigned', ['rider_id' => $riderId]);

        if ($terminalStatus === OrderStatus::RiderAssigned) {
            return;
        }

        $order = $this->transition($order, OrderStatus::PickedUp, 'order.picked_up');

        if ($terminalStatus === OrderStatus::PickedUp) {
            return;
        }

        $this->transition($order, OrderStatus::Delivered, 'order.delivered');
    }

    /** @param array<string, int> $attributes */
    private function transition(Order $order, OrderStatus $status, string $eventType, array $attributes = []): Order
    {
        $order->update([...$attributes, 'status' => $status]);
        $order = $order->fresh(['client', 'items', 'restaurant']);
        $this->events->record($order, $eventType);

        return $order;
    }
}
