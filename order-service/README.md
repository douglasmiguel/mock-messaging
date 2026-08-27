# Order Service

<p align="center">
  <img src="../assets/growth-loop-logo.png" alt="Growth Loop logo" width="96">
</p>

The Order Service is the source of truth for restaurant orders. It creates order records, records domain events in a transactional outbox, and exposes the public, admin, internal, and business-metrics endpoints.

Local site: [https://order-service.test](https://order-service.test)

## Data ownership

- Restaurants, restaurant item categories, and restaurant menu items.
- Clients and their email addresses.
- Orders and order item price snapshots.
- Transactional outbox messages.

Riders and their vehicles belong to the Rider Service. An order stores only the assigned external `rider_id`.

## Interfaces

| Interface | Purpose |
| --- | --- |
| `POST /v1/order` | Creates an order and records `order.placed` in the outbox. |
| `/` | Test-order generator and order-flow controls. |
| `/admin` | Paginated order list, restaurant search, expandable details. |
| `GET /metrics/business` | Prometheus metrics for order counts by status and restaurant. |
| Internal order routes | Called by Notification and Rider Services; protected by `LOCAL_SERVICE_KEY`. |

Admin credentials: `admin@order-service.test` / `admin`.

## Messages published

The outbox publishes to RabbitMQ's topic exchange. Every payload has a stable `event_id`, which is also sent as the AMQP message ID.

- `order.placed`
- `order.accepted`
- `order.refused`
- `order.ready_for_pickup`
- `order.rider_assigned`
- `order.delivered`
- `order.cancelled`
- `order.issue_raised`

The scheduler runs `outbox:publish` every second. Publisher confirms are awaited before an outbox record is marked published, so a process failure leaves the row available for a later retry.

## Local setup

```bash
cd /Users/douglas.miguel/dev/mock-messaging/order-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Set `RABBITMQ_*` values in `.env` for the local broker and configure the same `LOCAL_SERVICE_KEY` that the Notification and Rider Services use. The expected application URL is `https://order-service.test`.

To reset local data, use the destructive command:

```bash
php artisan migrate:fresh --seed
```

The seed creates restaurants, clients, menu data, and 100 historical orders in varied statuses.

## Required process

Start this in its own terminal whenever you want async events to leave this service:

```bash
php artisan schedule:work
```

For a one-off manual publish during debugging:

```bash
php artisan outbox:publish --limit=100
```

You can also publish messages for a single order using `php artisan outbox:publish --order=<order-id>`.

## Business metrics

`/metrics/business` is scraped by Prometheus. It exposes current order counts by status and restaurant, plus counts grouped by the UTC day an order was created. The date-series metric is a creation-date cohort with the order's current status; it is not a historical status-transition ledger.

The associated Grafana dashboard is **Order Service Business**.

## Generate Grafana demo traffic

With the scheduler and all service consumers already running, generate a representative set of orders:

```bash
php artisan demo:generate-orders --count=300 --days=14
```

It creates a balanced spread of all current order statuses, records the lifecycle events in the transactional outbox, and uses rider IDs 1–20 from the Rider Service seed. Give the consumers and Prometheus around 15 seconds to process and scrape the results before refreshing Grafana.

## Verification

```bash
php artisan test --compact
```

For the complete system restart order, see the [root runbook](../README.md).
