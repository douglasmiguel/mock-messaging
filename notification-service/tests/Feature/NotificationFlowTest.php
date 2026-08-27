<?php

namespace Tests\Feature;

use App\Mail\OrderNotificationMail;
use App\Models\ActionToken;
use App\Models\OrderSnapshot;
use App\Services\NotificationEventHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_placed_event_creates_action_tokens_and_sends_both_emails(): void
    {
        Mail::fake();

        app(NotificationEventHandler::class)->handle($this->event('order.placed', 'placed'));

        $this->assertDatabaseHas('order_snapshots', ['order_id' => 9001, 'status' => 'placed']);
        $this->assertDatabaseCount('action_tokens', 2);
        $this->assertDatabaseMissing('action_tokens', ['token_hash' => str_repeat('r', 64)]);
        $this->assertDatabaseHas('processed_events', ['event_id' => 'event-order.placed-1']);
        $this->assertDatabaseCount('notification_deliveries', 2);
        Mail::assertSent(OrderNotificationMail::class, 2);
    }

    public function test_duplicate_event_reuses_durable_deliveries_without_sending_again(): void
    {
        Mail::fake();

        $handler = app(NotificationEventHandler::class);
        $handler->handle($this->event('order.placed', 'placed'));
        $handler->handle($this->event('order.placed', 'placed'));

        $this->assertDatabaseCount('notification_deliveries', 2);
        Mail::assertSent(OrderNotificationMail::class, 2);
    }

    public function test_restaurant_action_calls_order_service_and_updates_the_snapshot(): void
    {
        $rawToken = str_repeat('r', 64);
        ActionToken::query()->create([
            'order_id' => 9001,
            'actor' => 'restaurant',
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => now()->addHour(),
        ]);
        OrderSnapshot::query()->create([
            'order_id' => 9001,
            'status' => 'placed',
            'aggregate_version' => 1,
            'restaurant_name' => 'Test Restaurant',
            'restaurant_email' => 'restaurant@example.test',
            'client_name' => 'Test Client',
            'client_email' => 'client@example.test',
            'payload' => [],
        ]);
        Http::fake([
            'https://order-service.test/api/v1/internal/orders/9001/restaurant/confirm' => Http::response([
                'data' => ['status' => 'accepted'],
            ]),
        ]);

        $this->post(route('restaurant-orders.confirm', $rawToken))
            ->assertRedirect(route('restaurant-orders.show', $rawToken));

        $this->assertDatabaseHas('order_snapshots', ['order_id' => 9001, 'status' => 'accepted']);
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('X-Service-Key'));
    }

    public function test_late_events_do_not_move_the_snapshot_backwards(): void
    {
        Mail::fake();
        $handler = app(NotificationEventHandler::class);
        $handler->handle($this->event('order.rider_assigned', 'rider_assigned', 3));
        $handler->handle($this->event('order.placed', 'placed', 1));

        $this->assertDatabaseHas('order_snapshots', [
            'order_id' => 9001,
            'status' => 'rider_assigned',
            'aggregate_version' => 3,
        ]);
    }

    /** @return array<string, mixed> */
    private function event(string $type, string $status, int $aggregateVersion = 1): array
    {
        return [
            'event_id' => 'event-'.$type.'-'.$aggregateVersion,
            'event_type' => $type,
            'event_version' => 1,
            'aggregate_type' => 'order',
            'aggregate_id' => '9001',
            'aggregate_version' => $aggregateVersion,
            'data' => [
                'order' => ['id' => 9001, 'status' => $status, 'items' => []],
                'restaurant' => ['name' => 'Test Restaurant', 'email' => 'restaurant@example.test'],
                'client' => ['name' => 'Test Client', 'email' => 'client@example.test'],
            ],
        ];
    }
}
