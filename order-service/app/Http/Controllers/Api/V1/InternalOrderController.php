<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\IdempotencyKey;
use App\Models\Order;
use App\Services\OrderEventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InternalOrderController extends Controller
{
    public function __construct(private readonly OrderEventRecorder $events) {}

    public function restaurantConfirm(Request $request, Order $order): JsonResponse
    {
        return $this->transition($request, $order, [OrderStatus::New, OrderStatus::Placed], OrderStatus::Accepted, 'order.accepted');
    }

    public function restaurantRefuse(Request $request, Order $order): JsonResponse
    {
        return $this->transition($request, $order, [OrderStatus::New, OrderStatus::Placed], OrderStatus::Cancelled, 'order.refused');
    }

    public function restaurantReady(Request $request, Order $order): JsonResponse
    {
        return $this->transition($request, $order, [OrderStatus::Accepted], OrderStatus::ReadyForPickup, 'order.ready_for_pickup');
    }

    public function clientCancel(Request $request, Order $order): JsonResponse
    {
        return $this->transition($request, $order, [OrderStatus::New, OrderStatus::Placed, OrderStatus::Accepted], OrderStatus::Cancelled, 'order.cancelled');
    }

    public function assignRider(Request $request, Order $order): JsonResponse
    {
        $this->authorizeService($request);
        $data = $request->validate(['rider_id' => ['required', 'integer', 'min:1']]);
        $idempotencyKey = (string) $request->header('X-Idempotency-Key');

        if ($idempotencyKey === '') {
            throw ValidationException::withMessages([
                'idempotency_key' => 'The X-Idempotency-Key header is required for rider assignment.',
            ]);
        }

        $requestHash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
        $existingKey = IdempotencyKey::query()
            ->where('operation', 'order.assign_rider')
            ->where('key', $idempotencyKey)
            ->first();

        if ($existingKey !== null) {
            if ($existingKey->order_id !== $order->id || ! hash_equals($existingKey->request_hash, $requestHash)) {
                throw ValidationException::withMessages([
                    'idempotency_key' => 'This idempotency key has already been used for a different rider assignment.',
                ]);
            }

            return $this->responseFor($existingKey->order()->firstOrFail());
        }

        return $this->transition(
            $request,
            $order,
            [OrderStatus::ReadyForPickup],
            OrderStatus::RiderAssigned,
            'order.rider_assigned',
            ['rider_id' => $data['rider_id']],
            ['operation' => 'order.assign_rider', 'key' => $idempotencyKey, 'request_hash' => $requestHash],
        );
    }

    public function show(Request $request, Order $order): JsonResponse
    {
        $this->authorizeService($request);

        return $this->responseFor($order);
    }

    /**
     * @param  list<OrderStatus>  $from
     * @param  array<string, int>  $attributes
     * @param  array{operation: string, key: string, request_hash: string}|null  $idempotency
     */
    private function transition(
        Request $request,
        Order $order,
        array $from,
        OrderStatus $to,
        string $eventType,
        array $attributes = [],
        ?array $idempotency = null,
    ): JsonResponse {
        $this->authorizeService($request);

        $updatedOrder = DB::transaction(function () use ($order, $from, $to, $eventType, $attributes, $idempotency): ?Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($order->status, $from, true)) {
                return null;
            }

            $order->update([...$attributes, 'status' => $to, 'aggregate_version' => $order->aggregate_version + 1]);
            $order = $order->fresh(['client', 'items', 'restaurant']);
            $this->events->record($order, $eventType);

            if ($idempotency !== null) {
                IdempotencyKey::query()->create([
                    ...$idempotency,
                    'order_id' => $order->id,
                    'response_status' => 200,
                ]);
            }

            return $order;
        });

        if ($updatedOrder === null) {
            return response()->json(['message' => 'The requested transition is not available.'], 409);
        }

        return $this->responseFor($updatedOrder);
    }

    private function authorizeService(Request $request): void
    {
        if (! hash_equals(config('messaging.service_key'), (string) $request->header('X-Service-Key'))) {
            abort(403);
        }
    }

    private function responseFor(Order $order): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $order->id,
                'rider_id' => $order->rider_id,
                'status' => $order->status->value,
            ],
        ]);
    }
}
