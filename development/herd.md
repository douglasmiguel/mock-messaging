# Laravel Herd development setup

Laravel Herd is an optional local-development convenience. It can provide PHP and HTTPS sites, but you can install the project's requirements independently instead; see the [root README](../README.md).

These instructions assume the repository has been cloned somewhere on your computer. In the commands below, replace `<repository>` with the path to that clone.

## Prerequisites

Install or make available:

- PHP 8.3 and Composer (Herd can provide the PHP runtime)
- SQLite
- RabbitMQ and Mailpit
- Prometheus and Grafana
- Node.js and npm when you need to build frontend assets

Use your operating system's package manager or the tools' official installation instructions. The project does not rely on a particular package manager or a specific home-directory layout.

## Configure the Laravel sites

From each service directory, create and secure the Herd site:

```bash
cd <repository>/order-service
herd link order-service
herd secure order-service

cd <repository>/notification-service
herd link notification-service
herd secure notification-service

cd <repository>/rider-service
herd link rider-service
herd secure rider-service

cd <repository>/observability-service
herd link observability-service
herd secure observability-service
```

The application sites are then available at:

| Service | URL |
| --- | --- |
| Order Service | <https://order-service.test> |
| Notification Service | <https://notification-service.test> |
| Rider Service | <https://rider-service.test> |
| Observability Service | <https://observability-service.test> |

## Configure infrastructure proxies

Start RabbitMQ, Mailpit, Prometheus, and Grafana using your chosen local installation method. Then add optional Herd proxies to give their web interfaces stable local URLs:

```bash
herd proxy rabbitmq http://127.0.0.1:15672 --secure
herd proxy grafana http://127.0.0.1:3000 --secure
herd proxy prometheus http://127.0.0.1:9090 --secure
herd proxy localmail http://127.0.0.1:8025
```

| Tool | URL |
| --- | --- |
| RabbitMQ management | <https://rabbitmq.test> |
| Mailpit | <http://localmail.test> |
| Grafana | <https://grafana.test> |
| Prometheus | <https://prometheus.test> |

RabbitMQ's default local credentials are `guest` / `guest`. Set Grafana credentials locally; do not add them to the repository.

## Configure each service

For every service, install dependencies, create an environment file, generate the Laravel key, and migrate the local SQLite database:

```bash
cd <repository>/<service>
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
```

Set `RABBITMQ_HOST=127.0.0.1` in every service's `.env`. In the Notification and Rider Services, set `ORDER_SERVICE_URL=https://order-service.test`; set `MAIL_MAILER=smtp`, `MAIL_HOST=127.0.0.1`, and `MAIL_PORT=1025` in the Notification Service. Use the same `LOCAL_SERVICE_KEY` in the Order, Notification, and Rider Services.

## Run metrics locally

Start the Observability Service metrics endpoint separately from the Herd site:

```bash
cd <repository>/observability-service
php artisan serve --host=127.0.0.1 --port=8081
```

Configure Prometheus to scrape that endpoint and the Order Service. The supplied [`prometheus.yml`](../observability-service/prometheus.yml) uses `127.0.0.1:8081` and `https://order-service.test/metrics/business`; make sure your local hostname resolution can reach `order-service.test`.

For Grafana provisioning, set these environment variables before starting Grafana so the checked-in provisioning files work from any clone location:

```bash
export PROMETHEUS_URL=http://127.0.0.1:9090
export DASHBOARD_PATH=<repository>/observability-service/grafana/dashboards
```

Start Grafana from `<repository>/observability-service` with its configuration file (`grafana.ini`), which sets the provisioning path relative to that directory. Use the start command appropriate to your operating system.

Finally, follow the [root README](../README.md#start-the-event-processing-processes) to provision RabbitMQ and start the scheduler and consumers.
