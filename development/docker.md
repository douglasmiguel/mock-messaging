# Docker Compose development setup

Docker Compose runs all four Laravel services, their long-running workers, RabbitMQ, Mailpit, Prometheus, and Grafana. It is an alternative to the optional [Laravel Herd setup](herd.md), so PHP, RabbitMQ, Mailpit, Prometheus, and Grafana do not need to be installed on the host.

## Prerequisite

Install Docker Desktop or another Docker Engine installation that includes Docker Compose.

## Initialise the Laravel applications

From the repository root, build the shared PHP image and initialise each service. The commands create ignored `.env` files and SQLite databases in your working copy; their PHP dependencies are kept in Docker volumes.

```bash
docker compose build

for service in order-service notification-service rider-service observability-service; do
  docker compose run --rm --no-deps "$service" sh -c '
    test -f .env || cp .env.example .env
    touch database/database.sqlite
    composer install --no-interaction --prefer-dist
    php artisan key:generate --force
    php artisan migrate:fresh --seed --force
  '
done
```

The Compose configuration supplies the service-to-service settings: RabbitMQ is `rabbitmq`, the Notification and Rider Services call `http://order-service:8000`, and Mailpit is `mailpit:1025`. The default local `LOCAL_SERVICE_KEY` is development-only; replace it with a private value if you alter the configuration.

## Start the stack

```bash
docker compose up -d
docker compose exec order-service php artisan messaging:provision-topology
```

Provision the RabbitMQ topology before generating orders. The scheduler and consumers are already running as Compose services; consumers retry automatically while the topology is being created.

## Local URLs

| Service | URL |
| --- | --- |
| Order Service | <http://localhost:8000> |
| Notification Service | <http://localhost:8001> |
| Rider Service | <http://localhost:8002> |
| Observability Service | <http://localhost:8003> |
| RabbitMQ management | <http://localhost:15672> |
| Mailpit | <http://localhost:8025> |
| Prometheus | <http://localhost:9090> |
| Grafana | <http://localhost:3000> |

RabbitMQ's local credentials are `guest` / `guest`. Grafana uses its container default credentials unless you override them in `compose.yaml`.

## Useful commands

```bash
# Generate demo traffic.
docker compose exec order-service php artisan demo:generate-orders --count=300 --days=14

# Follow a worker or application log.
docker compose logs -f order-scheduler
docker compose logs -f notification-consumer

# Stop containers while preserving databases and PHP dependency volumes.
docker compose down
```

To remove Docker-managed dependencies and infrastructure data as well, use `docker compose down --volumes`. This does not remove the SQLite databases created in the service directories; remove or reset those deliberately with the Laravel migration commands.
