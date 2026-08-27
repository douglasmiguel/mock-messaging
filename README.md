# Mock Messaging

<p align="center">
  <img src="assets/growth-loop-logo.png" alt="Growth Loop logo" width="120">
</p>

Mock Messaging is an event-driven food-ordering example built from four Laravel services and RabbitMQ. It is intended for learning the full asynchronous flow: transactional outbox publishing, idempotent consumers, retries, dead-letter queues, notifications, and metrics.

## Choose a development environment

The project does not require a particular local-machine setup. Choose one of the following approaches:

- **Install the requirements independently.** You need PHP 8.3, Composer, SQLite, RabbitMQ, Mailpit, Prometheus, and Grafana. Node.js and npm are also needed when you change frontend assets.
- **Use Laravel Herd (optional).** Herd is a convenient way to provide PHP and local HTTPS sites, but it is not a project requirement. Follow the [Herd setup guide](development/herd.md).
- **Use Docker Compose.** Docker runs the whole application and infrastructure stack without installing PHP, RabbitMQ, Mailpit, Prometheus, or Grafana on the host. Follow the [Docker setup guide](development/docker.md).

Every service has an `.env.example`. Create a local `.env` for each service and keep the following values consistent in whichever environment you choose:

- All services use the same `LOCAL_SERVICE_KEY`.
- Every service connects to the same RabbitMQ broker.
- The Notification and Rider Services use an `ORDER_SERVICE_URL` that they can reach from their own runtime.
- The Notification Service uses the Mailpit SMTP host and port for its runtime.

## Services

| Service | Purpose |
| --- | --- |
| Order Service | Owns orders, restaurants, clients, and the transactional outbox. |
| Notification Service | Consumes order events and sends emails through Mailpit. |
| Rider Service | Owns riders and assigns an available rider to ready orders. |
| Observability Service | Consumes all events and exposes Prometheus metrics. |
| RabbitMQ | Delivers events, retries, and dead-letter messages. |
| Mailpit | Captures local emails. |
| Prometheus and Grafana | Scrape and display technical and business metrics. |

The exact local URLs depend on the selected environment; both setup guides list them.

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

## Initialise local data

After setting up an environment, run migrations and seeds for each service before a clean local exercise. Run these commands from each service directory:

```bash
php artisan migrate:fresh --seed
```

`migrate:fresh --seed` deletes that service's local database. Use `php artisan migrate` instead when you need to preserve existing records.

The Order Service seeds restaurants, clients, menus, and 100 historical orders across statuses. The Rider Service seeds the riders it owns. The Order Service admin account is `admin@order-service.test` / `admin`.

## Start the event-processing processes

The environment guides start the web applications and supporting infrastructure. To process events, start these long-running commands in separate terminals (or use the Docker equivalents in the [Docker guide](development/docker.md)):

```bash
# order-service
php artisan messaging:provision-topology
php artisan schedule:work

# notification-service
php artisan notifications:consume

# rider-service
php artisan riders:consume

# observability-service
php artisan observability:consume
```

Provision the RabbitMQ topology before starting the scheduler so confirmed publishes are not dropped because queues or bindings do not exist.

## Quick smoke test

1. Open the Order Service and generate a test order.
2. Confirm an email appears in Mailpit. Use the restaurant action link to accept and then mark the order ready.
3. Confirm the Rider Service assigns a rider and the client receives the rider-on-the-way email.
4. Inspect queues and failures in RabbitMQ.
5. Inspect event flow in Grafana, including the **Order Service Business** dashboard.

## Populate Grafana with demo traffic

After every event-processing process is running, generate a spread of recent orders from the Order Service directory:

```bash
php artisan demo:generate-orders --count=300 --days=14
```

The command creates orders across every current status, spreads their creation dates across the past 14 days, and records lifecycle events in the transactional outbox. It uses external rider IDs 1–20, which match the Rider Service's seeded riders. Wait about 15 seconds for RabbitMQ delivery and Prometheus scraping before refreshing Grafana.

## Operational recovery

- `php artisan riders:reconcile` reports pending rider assignments that disagree with the Order Service; add `--apply` only after reviewing the dry run.
- `php artisan observability:rebuild-projections` reports the retained event count; add `--apply` to replace derived projections from ordered, versioned domain events.
- Retry queues use exponential backoff with jitter and dead-letter invalid event envelopes immediately. Redrive a DLQ message only after fixing the underlying cause; the event ID and aggregate version keep derived-state effects idempotent.
- Prometheus alerts when the outbox is stale for two minutes or a consumer has failed recently. The metrics and alerts are intentionally local defaults; set production SLOs and paging routes with the owning operations team.

See each service README for its data ownership, messages, commands, and troubleshooting details.
