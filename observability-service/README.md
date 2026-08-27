# Observability Service

<p align="center">
  <img src="../assets/growth-loop-logo.png" alt="Growth Loop logo" width="96">
</p>

The Observability Service listens to every event published to RabbitMQ, stores operational observations, and exposes Prometheus metrics. Prometheus and Grafana are configured here so the technical event flow and the Order Service business view can be explored locally.

Local site: [https://observability-service.test](https://observability-service.test)  
Metrics listener: `http://127.0.0.1:8081/metrics`  
Prometheus: [https://prometheus.test](https://prometheus.test)  
Grafana: [https://grafana.test](https://grafana.test)

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

```bash
cd /Users/douglas.miguel/dev/mock-messaging/observability-service
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
```

Configure local RabbitMQ values in `.env`. To reset only this service's data:

```bash
php artisan migrate:fresh --seed
```

## Required Laravel processes

Start the event consumer:

```bash
php artisan observability:consume
```

Start the dedicated metrics listener that Prometheus scrapes:

```bash
php artisan serve --host=127.0.0.1 --port=8081
```

The metrics listener is intentionally separate from the Herd site because Prometheus is configured to scrape `127.0.0.1:8081`.

## Prometheus

Start it with the repository configuration:

```bash
prometheus --config.file=/Users/douglas.miguel/dev/mock-messaging/observability-service/prometheus.yml --web.listen-address=127.0.0.1:9090
```

It scrapes this service every five seconds and the Order Service business metrics at `https://order-service.test/metrics/business`. For that HTTPS target to resolve from the Prometheus process, `/etc/hosts` needs:

```text
127.0.0.1 order-service.test
```

Restart Prometheus after changing `prometheus.yml`.

## Grafana

`grafana.ini` provisions the Prometheus datasource and the dashboards in `grafana/dashboards/`.

```bash
cd /Users/douglas.miguel/dev/mock-messaging/observability-service
grafana server \
  --config "$PWD/grafana.ini" \
  --homepath "$(brew --prefix grafana)/share/grafana" \
  --packaging=brew \
  cfg:default.paths.data="$(brew --prefix)/var/lib/grafana" \
  cfg:default.paths.logs="$(brew --prefix)/var/log/grafana" \
  cfg:default.paths.plugins="$(brew --prefix)/var/lib/grafana/plugins"
```

Open [Grafana](https://grafana.test) and sign in with the local credentials currently configured on this machine. Two dashboards are available:

- **Mock Messaging Overview** — event volume, status projections, retries, DLQs, and consumer health.
- **Order Service Business** — current orders by status and restaurant plus creation-date cohorts.

Restart Grafana after changing provisioned datasource or dashboard files.

## Verification

```bash
php artisan test --compact
```

For all processes required for an end-to-end test, see the [root runbook](../README.md).
