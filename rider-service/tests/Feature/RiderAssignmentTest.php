<?php

namespace Tests\Feature;

use App\Models\Rider;
use App\Services\RiderEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RiderAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_ready_for_pickup_event_assigns_an_available_rider_and_calls_order_service(): void
    {
        $rider = Rider::factory()->create();
        Http::fake([
            'https://order-service.test/api/v1/internal/orders/9001/rider-assignment' => Http::response([
                'data' => ['status' => 'rider_assigned'],
            ]),
        ]);

        app(RiderEventHandler::class)->handle($this->event('order.ready_for_pickup'));

        $this->assertDatabaseHas('rider_assignments', [
            'order_id' => 9001,
            'rider_id' => $rider->id,
            'status' => 'confirmed',
        ]);
        $this->assertDatabaseHas('processed_events', ['event_id' => 'event-order.ready_for_pickup']);
        Http::assertSent(fn (Request $request): bool => $request->data()['rider_id'] === $rider->id && $request->hasHeader('X-Idempotency-Key'));
    }

    public function test_delivered_event_releases_the_rider(): void
    {
        $rider = Rider::factory()->create();
        $rider->assignments()->create(['assignment_id' => '01J4T123456789ABCDEFGHJKMNP', 'order_id' => 9001, 'status' => 'confirmed']);

        app(RiderEventHandler::class)->handle($this->event('order.delivered'));

        $this->assertDatabaseHas('rider_assignments', ['order_id' => 9001, 'status' => 'completed']);
    }

    /** @return array<string, mixed> */
    private function event(string $type): array
    {
        return [
            'event_id' => 'event-'.$type,
            'event_type' => $type,
            'event_version' => 1,
            'aggregate_type' => 'order',
            'aggregate_id' => '9001',
            'aggregate_version' => 1,
            'data' => ['order' => ['id' => 9001]],
        ];
    }
}
