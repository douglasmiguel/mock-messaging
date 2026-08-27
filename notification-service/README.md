# Notification Service

<p align="center">
  <img src="../assets/growth-loop-logo.png" alt="Growth Loop logo" width="96">
</p>

The Notification Service consumes order events, records notification deliveries, and sends local emails through Mailpit. It owns user-facing action links for restaurant confirmation/refusal/ready actions and client cancellation or issue actions.

Local URLs depend on the selected development environment. The [Herd guide](../development/herd.md) uses `https://notification-service.test` and `http://localmail.test`; the [Docker guide](../development/docker.md) uses `http://localhost:8001` and `http://localhost:8025`.

## Data ownership

- Processed event IDs for consumer idempotency.
- Notification delivery records and email snapshots.
- Opaque action tokens for restaurant and client links.
- Client-reported order issues.

It does not own orders, restaurants, clients, or riders; it receives enough event data to create messages and uses a signed internal request to ask the Order Service to change state.

## Messages consumed

| Routing key | Email/action |
| --- | --- |
| `order.placed` | Restaurant confirmation email and client order-placed email. |
| `order.accepted` | Client update with cancellation option. |
| `order.refused` | Client refusal update. |
| `order.rider_assigned` | Client rider-on-the-way email with issue option. |

The service records an event before processing it. Delivery records also have a unique event/recipient/template identity, avoiding ordinary duplicate delivery when RabbitMQ redelivers an event. As with any email provider, a crash after SMTP accepts an email but before the local record is updated can still result in a duplicate email on retry.

## Queue, retry, and DLQ

- Primary queue: `notification-service.orders`
- Retry queue: `notification-service.orders.retry` (5-second delay)
- Dead-letter queue: `notification-service.orders.dlq`

Failures are retried up to three times. A fourth failed attempt is routed to the DLQ, where it can be inspected in RabbitMQ without blocking the rest of the flow.

## Local setup

```bash
cd <repository>/notification-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Set these values in `.env`:

- RabbitMQ connection details (`RABBITMQ_*`).
- `MAIL_MAILER=smtp`, plus the Mailpit host and port reachable from this service.
- `ORDER_SERVICE_URL`, set to the URL reachable from this service's runtime (see the environment guide).
- `LOCAL_SERVICE_KEY`, identical to the Order Service value.

To reset only this service's local data:

```bash
php artisan migrate:fresh --seed
```

## Required consumer

```bash
php artisan notifications:consume
```

Use `--once` to consume a single message during debugging. The consumer must remain running for emails and action links to be created.

## Verification

```bash
php artisan test --compact
```

For all processes required for an end-to-end test, see the [root guide](../README.md).
