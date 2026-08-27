# Rider Service

<p align="center">
  <img src="../assets/growth-loop-logo.png" alt="Growth Loop logo" width="96">
</p>

The Rider Service owns rider availability and assignments. When an order is ready for pickup, it selects an available rider, stores an assignment, and tells the Order Service which external rider ID was assigned.

The local URL depends on the selected development environment: the [Herd guide](../development/herd.md) uses `https://rider-service.test`, while the [Docker guide](../development/docker.md) uses `http://localhost:8002`.

## Data ownership

- Riders, including name, vehicle name, and licence.
- Rider assignments and their lifecycle.
- Processed event IDs for idempotent event handling.

The Order Service retains only the resulting `rider_id` on an order; it does not duplicate rider records.

## Messages consumed and effects

| Routing key | Effect |
| --- | --- |
| `order.ready_for_pickup` | Select the first rider without an active ride, create one assignment per order, then call the Order Service to assign the rider. |
| `order.delivered` | Complete the matching assignment so the rider becomes available again. |

The internal assignment call causes the Order Service to publish `order.rider_assigned`, which the Notification Service uses for the rider-on-the-way email.

## Queue, retry, and DLQ

- Primary queue: `rider-service.orders`
- Retry queue: `rider-service.orders.retry` (5-second delay)
- Dead-letter queue: `rider-service.orders.dlq`

The consumer retries failures three times; the next failed attempt goes to the DLQ. Processed-event and assignment uniqueness checks make redelivery safe.

## Local setup

```bash
cd <repository>/rider-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Set `RABBITMQ_*`, an `ORDER_SERVICE_URL` reachable from this service's runtime, and a `LOCAL_SERVICE_KEY` that matches the Order Service. The environment guides provide the Herd and Docker values.

The seed provides 20 riders. To reset this service's database:

```bash
php artisan migrate:fresh --seed
```

## Required consumer

```bash
php artisan riders:consume
```

Use `--once` to consume a single message while debugging. Leave the normal consumer running during end-to-end testing.

## Verification

```bash
php artisan test --compact
```

For all processes required for an end-to-end test, see the [root guide](../README.md).
