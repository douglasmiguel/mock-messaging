<?php

namespace App\Http\Controllers\TestOrder;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\Restaurant;
use App\Services\OrderEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenerateTestOrderController extends Controller
{
    public function __construct(private readonly OrderEventRecorder $events) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $restaurant = Restaurant::query()
            ->whereHas('items', fn ($query) => $query->where('is_available', true))
            ->inRandomOrder()
            ->firstOrFail();
        $client = Client::query()->inRandomOrder()->firstOrFail();
        $menuItems = $restaurant->items()
            ->where('is_available', true)
            ->inRandomOrder()
            ->limit(2)
            ->get();

        $order = DB::transaction(function () use ($restaurant, $client, $menuItems): Order {
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
                'status' => OrderStatus::New,
            ]);
            $order->items()->createMany($orderItems->all());
            $this->events->record($order, 'order.placed');

            return $order;
        });

        $request->session()->put('test_order_id', $order->id);

        return redirect()->route('home')->with('success', "Test order #{$order->id} was created.");
    }
}
