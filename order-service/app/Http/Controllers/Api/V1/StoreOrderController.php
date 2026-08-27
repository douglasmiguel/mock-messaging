<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Models\Client;
use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Models\RestaurantItem;
use App\Services\OrderEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StoreOrderController extends Controller
{
    public function __construct(private readonly OrderEventRecorder $events) {}

    public function __invoke(StoreOrderRequest $request): JsonResponse
    {
        $data = $request->validated();
        $requestHash = $this->requestHash($data);
        $existingKey = IdempotencyKey::query()
            ->where('operation', 'order.create')
            ->where('key', $data['idempotency_key'])
            ->first();

        if ($existingKey !== null) {
            if (! hash_equals($existingKey->request_hash, $requestHash)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'This idempotency key has already been used for a different request.',
                ]);
            }

            return $this->responseFor($existingKey->order()->with(['client', 'items'])->firstOrFail(), $existingKey->response_status);
        }

        $requestedItems = collect($data['items'])->keyBy('restaurant_item_id');
        $menuItems = RestaurantItem::query()
            ->where('restaurant_id', $data['restaurant_id'])
            ->where('is_available', true)
            ->whereIn('id', $requestedItems->keys())
            ->get()
            ->keyBy('id');

        if ($menuItems->count() !== $requestedItems->count()) {
            throw ValidationException::withMessages([
                'items' => 'Every item must be available on the selected restaurant menu.',
            ]);
        }

        $order = DB::transaction(function () use ($data, $menuItems, $requestedItems, $requestHash): Order {
            $orderPrice = $requestedItems->sum(function (array $requestedItem, int $restaurantItemId) use ($menuItems): int {
                return $menuItems->get($restaurantItemId)->item_price * $requestedItem['quantity'];
            });
            $deliveryFee = config('orders.delivery_fee');
            $client = Client::query()->updateOrCreate(
                ['email' => $data['client_email']],
                [
                    'name' => $data['client_name'],
                    'phone' => $data['client_phone'] ?? null,
                ],
            );

            $order = Order::create([
                'client_id' => $client->id,
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'] ?? null,
                'client_email' => $client->email,
                'delivery_address' => $data['delivery_address'],
                'restaurant_id' => $data['restaurant_id'],
                'order_price' => $orderPrice,
                'delivery_fee' => $deliveryFee,
                'total_price' => $orderPrice + $deliveryFee,
                'status' => OrderStatus::Placed,
                'aggregate_version' => 1,
            ]);

            $order->items()->createMany($requestedItems->map(function (array $requestedItem, int $restaurantItemId) use ($menuItems): array {
                $menuItem = $menuItems->get($restaurantItemId);

                return [
                    'restaurant_item_id' => $menuItem->id,
                    'item_name' => $menuItem->name,
                    'item_price' => $menuItem->item_price,
                    'quantity' => $requestedItem['quantity'],
                    'total_price' => $menuItem->item_price * $requestedItem['quantity'],
                ];
            })->values()->all());

            $this->events->record($order->fresh('restaurant'), 'order.placed');

            IdempotencyKey::query()->create([
                'operation' => 'order.create',
                'key' => $data['idempotency_key'],
                'request_hash' => $requestHash,
                'order_id' => $order->id,
                'response_status' => 201,
            ]);

            return $order->load(['client', 'items']);
        });

        return $this->responseFor($order, 201);
    }

    /** @param array<string, mixed> $data */
    private function requestHash(array $data): string
    {
        $canonical = Arr::except($data, ['idempotency_key']);
        $canonical['items'] = collect($canonical['items'])
            ->sortBy('restaurant_item_id')
            ->values()
            ->all();

        return hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
    }

    private function responseFor(Order $order, int $status): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $order->id,
                'client_name' => $order->client_name,
                'client_email' => $order->client_email,
                'delivery_address' => $order->delivery_address,
                'restaurant_id' => $order->restaurant_id,
                'order_price' => $order->order_price,
                'delivery_fee' => $order->delivery_fee,
                'total_price' => $order->total_price,
                'rider_id' => $order->rider_id,
                'status' => $order->status->value,
                'items' => $order->items->map(fn ($item): array => [
                    'restaurant_item_id' => $item->restaurant_item_id,
                    'item_name' => $item->item_name,
                    'item_price' => $item->item_price,
                    'quantity' => $item->quantity,
                    'total_price' => $item->total_price,
                ])->values(),
            ],
        ], $status);
    }
}
