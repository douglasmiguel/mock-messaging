<?php

namespace App\Http\Controllers\TestOrder;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderEventRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TestOrderFlowController extends Controller
{
    public function __construct(private readonly OrderEventRecorder $events) {}

    public function confirm(Request $request, Order $order): RedirectResponse
    {
        return $this->transition($request, $order, [OrderStatus::New], OrderStatus::Accepted, 'order.accepted');
    }

    public function pickUp(Request $request, Order $order): RedirectResponse
    {
        return $this->transition($request, $order, [OrderStatus::RiderAssigned], OrderStatus::PickedUp, 'order.picked_up');
    }

    public function ready(Request $request, Order $order): RedirectResponse
    {
        return $this->transition($request, $order, [OrderStatus::Accepted], OrderStatus::ReadyForPickup, 'order.ready_for_pickup');
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        return $this->transition($request, $order, [OrderStatus::PickedUp], OrderStatus::Delivered, 'order.delivered');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        return $this->transition($request, $order, [OrderStatus::New, OrderStatus::Accepted], OrderStatus::Cancelled, 'order.cancelled');
    }

    /**
     * @param  list<OrderStatus>  $from
     * @param  array<string, int>  $attributes
     */
    private function transition(
        Request $request,
        Order $order,
        array $from,
        OrderStatus $to,
        string $eventType,
        array $attributes = [],
    ): RedirectResponse {
        if ((int) $request->session()->get('test_order_id') !== $order->id) {
            abort(403);
        }

        $updated = DB::transaction(function () use ($order, $from, $to, $eventType, $attributes): bool {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, $from, true)) {
                return false;
            }

            $order->update([...$attributes, 'status' => $to]);
            $this->events->record($order->fresh('restaurant'), $eventType);

            return true;
        });

        if (! $updated) {
            return redirect()->route('home')->with('error', 'That step is not available for the current order status.');
        }

        return redirect()->route('home')->with('success', "Order #{$order->id} is now {$to->value}.");
    }
}
