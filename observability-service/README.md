# Observability Service

<p align="center">
  <img src="../assets/growth-loop-logo.png" alt="Growth Loop logo" width="96">
</p>

The Observability Service listens to every event published to RabbitMQ, stores operational observations, and exposes Prometheus metrics. Prometheus and Grafana are configured here so the technical event flow and the Order Service business view can be explored locally.

Local URLs depend on the selected development environment. The [Herd guide](../development/herd.md) uses the `*.test` URLs; the [Docker guide](../development/docker.md) exposes the service on port 8003, Prometheus on 9090, and Grafana on 3000.

## Data ownership

- Observed-event records and event projections.
- Consumer health and processing metrics.
- Processed event IDs for idempotent handling.

It is deliberately an independent consumer: it observes without changing Order, Rider, or Notification domain state.

## Messages and queues

The consumer binds to `#`, so it receives all routing keys on the messaging exchange.

- Primary queue: `observability-service.events`
- Retry queue: `observability-service.events.retry` (5-second delay)
- Dead-letter queue: `observability-service.events.dlq`

Events are deduplicated by event ID. Failures are retried three times and then delivered to the DLQ. The `/metrics` endpoint reports event counts, status projections, consumer health, retries, and dead-letter activity.

## Local setup

Follow either the [Herd guide](../development/herd.md) or the [Docker guide](../development/docker.md). For an independently installed PHP runtime, initialise this service from its directory:

```bash
cd <repository>/observability-service
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Configure `RABBITMQ_*` values that can reach your local broker. To reset only this service's data:

```bash
php artisan migrate:fresh --seed
```

## Required processes

The consumer runs with:

```bash
php artisan observability:consume
```

For the Herd approach, start the dedicated metrics listener that Prometheus scrapes:

```bash
php artisan serve --host=127.0.0.1 --port=8081
```

Docker serves the same `/metrics` endpoint from the Observability Service container, so no separate listener is needed. The environment guides contain the matching Prometheus and Grafana setup.

## Dashboards

Two provisioned Grafana dashboards are available:

- **Mock Messaging Overview** — event volume, status projections, retries, DLQs, and consumer health.
- **Order Service Business** — current orders by status and restaurant plus creation-date cohorts.

The provisioning files use the `PROMETHEUS_URL` and `DASHBOARD_PATH` environment variables. The environment guides provide the correct values for both runtime options.

## Verification

```bash
php artisan test --compact
```

For all processes required for an end-to-end test, see the [root guide](../README.md).
