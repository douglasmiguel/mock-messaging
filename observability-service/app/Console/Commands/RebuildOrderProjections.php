<?php

namespace App\Console\Commands;

use App\Models\ObservedEvent;
use App\Models\OrderProjection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildOrderProjections extends Command
{
    protected $signature = 'observability:rebuild-projections {--apply : Replace current projections; omit for a dry run}';

    protected $description = 'Rebuild order projections from retained, versioned domain events';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $events = ObservedEvent::query()->where('event_type', 'like', 'order.%')->count();
        if (! $this->option('apply')) {
            $this->info("Dry run: {$events} domain event(s) would rebuild the order projections.");

            return self::SUCCESS;
        }

        DB::transaction(function (): void {
            OrderProjection::query()->delete();
            ObservedEvent::query()
                ->where('event_type', 'like', 'order.%')
                ->orderBy('order_id')
                ->orderBy('occurred_at')
                ->orderBy('id')
                ->each(function (ObservedEvent $event): void {
                    $payload = $event->payload;
                    $aggregateVersion = (int) ($payload['aggregate_version'] ?? 0);
                    if ($aggregateVersion < 1 || ! is_numeric(data_get($payload, 'data.order.id'))) {
                        return;
                    }

                    $this->applyProjection($payload, $event);
                });
        });

        $this->info("Rebuilt projections from {$events} domain event(s).");

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $event */
    private function applyProjection(array $event, ObservedEvent $observed): void
    {
        $orderId = (int) data_get($event, 'data.order.id');
        $version = (int) $event['aggregate_version'];
        $projection = OrderProjection::query()->where('order_id', $orderId)->first();
        $attributes = [
            'status' => (string) data_get($event, 'data.order.status', 'unknown'),
            'aggregate_version' => $version,
            'restaurant_id' => data_get($event, 'data.restaurant.id'),
            'client_id' => data_get($event, 'data.client.id'),
            'rider_id' => data_get($event, 'data.rider_id'),
            'last_event_type' => $observed->event_type,
            'last_event_at' => $observed->occurred_at,
        ];

        if ($projection === null) {
            OrderProjection::query()->create(['order_id' => $orderId, ...$attributes]);

            return;
        }

        if ($version > $projection->aggregate_version) {
            $projection->update($attributes);
        }
    }
}
