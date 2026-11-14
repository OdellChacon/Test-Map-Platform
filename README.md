# Outage Map Platform

A power outage reporting platform with a live public map. Users report an outage at a point with a radius and a duration; anyone, logged in or not, sees active reports on a map that refreshes automatically.

## Stack

| Layer | Choice |
|---|---|
| Backend | PHP 8.4, Laravel 13, REST API |
| API auth | Laravel Sanctum, SPA cookie-based session |
| Frontend | Vue 3 (Composition API), Vue Router, Pinia |
| Database | PostgreSQL 16 with PostGIS |
| Cache / queues | Redis |
| Map | OpenStreetMap tiles via Leaflet |
| Real-time updates | Laravel Reverb (WebSocket), Laravel Echo |
| Reverse geocoding | Nominatim (OpenStreetMap) |
| Containers | Docker Compose |

Full justification for each choice is in [ARCHITECTURE.md](ARCHITECTURE.md). The milestone plan is in [PLAN.md](PLAN.md).

## Prerequisites

Docker and Docker Compose. Nothing else needs to be installed locally — no PHP, Node or PostgreSQL.

## Quick start

```bash
cp .env.example .env
cp api/.env.example api/.env
cp client/.env.example client/.env

docker compose build api
docker compose run --rm api php artisan key:generate

docker compose up -d

docker compose exec api php artisan migrate
docker compose exec api php artisan db:seed
```

The key must be generated with a one-off container, before the long-running services start: `docker-compose`'s `env_file` snapshots `api/.env` into each service's environment at container creation, so a later `docker compose exec api php artisan key:generate` would write the key to the file but the already-running `api`/`queue`/`scheduler` containers would never see it without a restart.

Open:
- Frontend: http://localhost:5173
- API health check: http://localhost:8000/up

Demo account created by the seeder:

```
demo@outagemap.test / password
```

## Running the test suite

Tests run against a dedicated `outage_map_test` database, created automatically from the `template_postgis` template the first time the `postgres` container starts (see [postgres/init](postgres/init)). They never touch the development database.

```bash
docker compose exec api ./vendor/bin/pest
```

## Code style

```bash
docker compose exec api ./vendor/bin/pint
```

## API documentation

The full OpenAPI 3.0 contract, with every endpoint, request/response schema and error shape, is at [api/openapi.yaml](api/openapi.yaml).

## Project structure

```
api/             Laravel 13 REST API
client/          Vue 3 SPA
nginx/           Reverse proxy configuration
postgres/init/   Database initialization scripts (PostGIS, test database)
docker-compose.yml
```

## Services and ports

| Service | Exposed port | Purpose |
|---|---|---|
| `client` | 5173 | Vite dev server (SPA) |
| `nginx` | 8000 | Reverse proxy in front of the API |
| `reverb` | 8081 | WebSocket server for the live public map |
| `api`, `queue`, `scheduler` | — | Same Laravel image, three entrypoints (php-fpm, `queue:work`, `schedule:work`) |
| `postgres`, `redis` | — | Internal only, not published to the host |

## Resetting the stack

```bash
docker compose down -v
```

Removes containers and named volumes (database and node_modules/vendor caches), for a true from-scratch run of the quick start above.

## Bonus features implemented

Beyond the mandatory scope (see [PLAN.md](PLAN.md) §4 and §10 of the brief):

- **Live updates over WebSockets** — the public map and "My reports" both subscribe to a public Reverb channel (`outages`) and update in real time (new report, closed, confirmed, address resolved), falling back to a 2-minute poll if a WebSocket message is ever missed. See ADR-7 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **Community confirmation** — any authenticated user other than a report's owner can confirm it exists; the count is shown on the public map and on the owner's own list. See ADR-4 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **Reverse geocoding** — a queued job resolves a human-readable address for every report via Nominatim, shown wherever the report appears. See ADR-8 in [ARCHITECTURE.md](ARCHITECTURE.md).

## Known limitations

The public map can still look visually noisy when many overlapping reports describe the same real-world outage (ADR-4); confirmation gives a corroboration signal but reports are never auto-merged.
