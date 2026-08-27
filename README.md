# Mock Messaging

<p align="center">
  <img src="assets/growth-loop-logo.png" alt="Growth Loop logo" width="120">
</p>

This is a local, event-driven food-ordering example built from four Laravel services and RabbitMQ. It is intended for learning the full async flow: transactional outbox publishing, idempotent consumers, retries, dead-letter queues, notifications, and metrics.

## Services and local URLs

| Service | Purpose | Local URL |
| --- | --- | --- |
| Order Service | Owns orders, restaurants, clients, and the transactional outbox | [https://order-service.test](https://order-service.test) |
| Notification Service | Consumes order events and sends emails through Mailpit | [https://notification-service.test](https://notification-service.test) |
| Rider Service | Owns riders and assigns an available rider to ready orders | [https://rider-service.test](https://rider-service.test) |
| Observability Service | Consumes all events and exposes Prometheus metrics | [https://observability-service.test](https://observability-service.test) |
| RabbitMQ management | Exchanges, queues, retries, and DLQs | [https://rabbitmq.test](https://rabbitmq.test) or [http://localhost:15672](http://localhost:15672) |
| Mailpit | Local email inbox | [http://localmail.test](http://localmail.test) |
| Grafana | Technical and business dashboards | [https://grafana.test](https://grafana.test) |
| Prometheus | Metrics scraper and query UI | [https://prometheus.test](https://prometheus.test) |

RabbitMQ uses the local `guest` / `guest` credentials. Grafana uses the password currently configured on this machine; it is intentionally not recorded here.

## Event flow

```text
Order Service --transactional outbox--> RabbitMQ topic exchange
                                         |       |       |
                                         v       v       v
                                Notification  Rider  Observability
                                   Service    Service    Service
                                     |          |
                                   Mailpit   Order Service internal API
```

The Order Service publishes `order.*` events through its outbox. Consumers use the event ID as their idempotency key. Each consumer has a retry queue (5-second delay, three retries) and a dead-letter queue for messages that still cannot be handled.

## First-time local setup

Install the local infrastructure if it is not already present:

```bash
brew install rabbitmq mailpit prometheus grafana
brew services start rabbitmq
brew services start mailpit
```

Or start the reproducible infrastructure stack with Docker:

```bash
cd /Users/douglas.miguel/dev/mock-messaging
docker compose up -d
```

Laravel Herd should have the four project directories linked and secured. Recreate the links if required:

```bash
cd /Users/douglas.miguel/dev/mock-messaging/order-service
herd link order-service
herd secure order-service

cd /Users/douglas.miguel/dev/mock-messaging/notification-service
herd link notification-service
herd secure notification-service

cd /Users/douglas.miguel/dev/mock-messaging/rider-service
herd link rider-service
herd secure rider-service

cd /Users/douglas.miguel/dev/mock-messaging/observability-service
herd link observability-service
herd secure observability-service
```

Create the non-Laravel Herd proxies if they are missing:

```bash
herd proxy rabbitmq http://127.0.0.1:15672 --secure
herd proxy grafana http://127.0.0.1:3000 --secure
herd proxy prometheus http://127.0.0.1:9090 --secure
herd proxy localmail http://127.0.0.1:8025
```

Each service has its own `.env`. Confirm that all three consumers use the same `LOCAL_SERVICE_KEY` as the Order Service and that their `ORDER_SERVICE_URL` is `https://order-service.test`.

## Recreate local data

Run migrations and seed each service before a clean local exercise:

```bash
cd /Users/douglas.miguel/dev/mock-messaging/order-service
php artisan migrate:fresh --seed

cd /Users/douglas.miguel/dev/mock-messaging/notification-service
php artisan migrate:fresh --seed

cd /Users/douglas.miguel/dev/mock-messaging/rider-service
php artisan migrate:fresh --seed

cd /Users/douglas.miguel/dev/mock-messaging/observability-service
php artisan migrate:fresh --seed
```

`migrate:fresh --seed` deletes that service's local database. Use `php artisan migrate` instead when you need to preserve existing records.

The Order Service seeds restaurants, clients, menus, and 100 historical orders across statuses. The Rider Service seeds the riders it owns. The Order Service admin account is `admin@order-service.test` / `admin`.

## Full restart runbook

After restarting the machine, open separate terminals and start the following processes. Keep them running while testing.

### 1. Start infrastructure

```bash
brew services start rabbitmq
brew services start mailpit
herd start
```

### 2. Provision the RabbitMQ topology

This must finish before the outbox publisher starts so that confirmed publishes are never dropped because queues or bindings do not yet exist.

```bash
cd /Users/douglas.miguel/dev/mock-messaging/order-service
php artisan messaging:provision-topology
```

### 3. Start the Order Service scheduler

This publishes pending transactional-outbox rows to RabbitMQ every second.

```bash
cd /Users/douglas.miguel/dev/mock-messaging/order-service
php artisan schedule:work
```

### 4. Start the three RabbitMQ consumers

```bash
cd /Users/douglas.miguel/dev/mock-messaging/notification-service
php artisan notifications:consume
```

```bash
cd /Users/douglas.miguel/dev/mock-messaging/rider-service
php artisan riders:consume
```

```bash
cd /Users/douglas.miguel/dev/mock-messaging/observability-service
php artisan observability:consume
```

### 5. Start the Observability metrics listener

Prometheus scrapes this service directly on port 8081. This is separate from its Herd site.

```bash
cd /Users/douglas.miguel/dev/mock-messaging/observability-service
php artisan serve --host=127.0.0.1 --port=8081
```

### 6. Start Prometheus

Prometheus must be able to resolve `order-service.test`. Ensure `/etc/hosts` contains this line (it may already be present):

```text
127.0.0.1 order-service.test
```

Then run:

```bash
prometheus --config.file=/Users/douglas.miguel/dev/mock-messaging/observability-service/prometheus.yml --web.listen-address=127.0.0.1:9090
```

### 7. Start Grafana

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

Grafana provisions the Prometheus datasource and two dashboards: **Mock Messaging Overview** and **Order Service Business**. Restart Grafana after changing a provisioned dashboard file. Restart Prometheus after changing `prometheus.yml`.

## Required running processes

| Process | Why it is needed |
| --- | --- |
| RabbitMQ | Delivers domain events, retry messages, and DLQs |
| Mailpit | Accepts and displays local email |
| Herd | Serves the Laravel sites and HTTPS routes |
| `order-service: php artisan schedule:work` | Publishes the transactional outbox |
| `notification-service: php artisan notifications:consume` | Sends notification emails |
| `rider-service: php artisan riders:consume` | Assigns and completes riders |
| `observability-service: php artisan observability:consume` | Stores event observations and consumer metrics |
| `observability-service: php artisan serve ...8081` | Exposes `/metrics` for Prometheus |
| Prometheus | Scrapes technical and business metrics |
| Grafana | Displays the provisioned dashboards |

## Quick smoke test

1. Open [Order Service](https://order-service.test) and generate a test order.
2. Confirm an email appears in [Mailpit](http://localmail.test). Use the restaurant action link to accept and then mark the order ready.
3. Confirm the Rider Service assigns a rider and the client receives the rider-on-the-way email.
4. Inspect queues and failures in [RabbitMQ](https://rabbitmq.test).
5. Inspect event flow in [Grafana](https://grafana.test), including the **Order Service Business** dashboard.

## Populate Grafana with demo traffic

After every process in the restart runbook is running, generate a spread of recent orders and lifecycle events:

```bash
cd /Users/douglas.miguel/dev/mock-messaging/order-service
php artisan demo:generate-orders --count=300 --days=14
```

The command creates orders across every current status, spreads their creation dates across the past 14 days, and records lifecycle events in the transactional outbox. It uses external rider IDs 1–20, which match the Rider Service's seeded riders. Leave the scheduler and consumers running, then wait about 15 seconds for RabbitMQ delivery and Prometheus scraping before refreshing Grafana.

See each service README for its data ownership, messages, commands, and troubleshooting details.

## Operational recovery

- `php artisan riders:reconcile` reports pending rider assignments that disagree with the Order Service; add `--apply` only after reviewing the dry run.
- `php artisan observability:rebuild-projections` reports the retained event count; add `--apply` to replace derived projections from ordered, versioned domain events.
- Retry queues use exponential backoff with jitter and dead-letter invalid event envelopes immediately. Redrive a DLQ message only after fixing the underlying cause; the event ID and aggregate version keep derived-state effects idempotent.
- Prometheus alerts when the outbox is stale for two minutes or a consumer has failed recently. The metrics and alerts are intentionally local defaults; set production SLOs and paging routes with the owning operations team.
