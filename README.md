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

docker compose up -d --build
```

That single command builds the images, starts every service and brings the stack to a ready state: the `api` container's entrypoint generates `APP_KEY` on first boot (if `api/.env` doesn't already have one), runs pending migrations, and seeds the demo data on a genuinely fresh database only (detected by the absence of a migrations table, so restarting the stack later never re-seeds and duplicates the demo accounts). `queue`, `scheduler`, `reverb` and `nginx` all wait on `api`'s health check, so they only start once that's done — which also means they pick up the generated `APP_KEY` from `api/.env` instead of the empty value `docker-compose`'s `env_file` would otherwise have snapshotted into their environment at container creation.

To run migrations or seeding again by hand later (e.g. after `docker compose down -v`):

```bash
docker compose exec api php artisan migrate
docker compose exec api php artisan db:seed
```

Open:
- Frontend: http://localhost:5173
- API health check: http://localhost:8000/up

Demo accounts created by the seeder:

```
demo@outagemap.test / password
moderator@outagemap.test / password   (is_moderator: true — can access /moderation)
```

`demo@outagemap.test`'s "My reports" comes pre-populated with 4 reports, so an evaluator sees a mix of states without creating anything first: 2 active, 1 closed and 1 expired (this also keeps the account below `max_active_reports_per_user`, so a new report can still be created live during the review). The seeder also creates 10 other users with 1–3 reports each, plus two small clusters of nearby reports (to exercise the public map's marker clustering), and a random spread of confirmations across the demo user's reports.

## Running the test suite

Tests run against a dedicated `outage_map_test` database, created automatically from the `template_postgis` template the first time the `postgres` container starts (see [postgres/init](postgres/init)). They never touch the development database.

```bash
docker compose exec api ./vendor/bin/pest
```

Frontend unit tests (Vitest, jsdom):

```bash
docker compose exec client npm run test
```

## Code style

```bash
docker compose exec api ./vendor/bin/pint
```

## Continuous integration

A GitHub Actions workflow ([.github/workflows/ci.yml](.github/workflows/ci.yml)) runs on every push and pull request against `main`: backend (Pint + Pest against a `postgis/postgis` service container), frontend (lint + Vitest + build), and a Docker build of both images.

## API documentation

The full OpenAPI 3.0 contract, with every endpoint, request/response schema and error shape, is at [api/openapi.yaml](api/openapi.yaml).

## Map provider

OpenStreetMap tiles via Leaflet (ADR-5 in [ARCHITECTURE.md](ARCHITECTURE.md)). **No API key, account or billing is required** — this is the whole reason that provider was chosen over Google Maps. Nothing to configure here beyond what the quick start already does.

## Reverb credentials

The `REVERB_APP_ID`, `REVERB_APP_KEY` and `REVERB_APP_SECRET` values in `api/.env.example` (and the matching `VITE_REVERB_APP_KEY` in `client/.env.example`) are local-dev-only placeholders. They MUST be regenerated (e.g. via `php artisan reverb:install` or manually) before any real or shared deployment.

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

- **Live updates over WebSockets (B1)** — the public map and "My reports" both subscribe to a public Reverb channel (`outages`) and update in real time (new report, closed, confirmed, address resolved), falling back to a 2-minute poll if a WebSocket message is ever missed. See ADR-7 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **Community confirmation (B2)** — any authenticated user other than a report's owner can confirm it exists; the count is shown on the public map and on the owner's own list. See ADR-4 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **Zone subscriptions (B3)** — an authenticated user draws a point + radius on the map ([SubscriptionsView.vue](client/src/views/SubscriptionsView.vue)); any new report inside that radius queues a database notification (bell icon in the navbar, unread badge) via `NotifySubscribersOfNewOutage`. See ADR-9 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **Frontend test suite (B4)** — Vitest + `@vue/test-utils`, covering services, the auth store and time-formatting utilities (`npm run test` in `client/`).
- **Marker clustering (B5)** — `leaflet.markercluster` groups nearby reports on the public map at low zoom levels instead of overlapping circles, with a custom cluster-count badge.
- **Moderator role (B6)** — users with `is_moderator` can hide or close any report from a moderation queue ([ModerationView.vue](client/src/views/ModerationView.vue)); every action is written to `report_moderation_actions` with the acting moderator's id. See ADR-10 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **CI pipeline (B7)** — see [Continuous integration](#continuous-integration) above.
- **Reverse geocoding (B8)** — a queued job resolves a human-readable address for every report via Nominatim, shown wherever the report appears. See ADR-8 in [ARCHITECTURE.md](ARCHITECTURE.md).
- **Outage history / statistics (B9)** — a public stats view ([HistoryView.vue](client/src/views/HistoryView.vue)) backed by `/api/v1/public/stats`, showing aggregate counts and a reports-per-day chart, computed in SQL with no individual report rows exposed.

## Assumptions

Decisions made where the brief left the choice open, beyond what's already argued as an ADR in [ARCHITECTURE.md](ARCHITECTURE.md):

- **Sanctum mode, database, map provider and frontend framework** (Sanctum SPA session, PostgreSQL+PostGIS, OpenStreetMap/Leaflet, Vue 3) are each a brief-sanctioned choice, not a gap — see ADR-1, ADR-2, ADR-5 and ADR-6 respectively for the reasoning.
- **"No personal data" on the public endpoint** was read strictly as no name, email or user id — not as "hide everything the reporter wrote." The public resource does include the outage's own `description`, `duration_type`, start time and expiry, because §4.4 of the brief asks for exactly those on click; only the reporter's identity is withheld.
- **Community confirmation (B2)** only adds a visible count; it does not auto-change a report's status or hide it after N confirmations. The brief allows either; changing visibility automatically felt like a second, unverified judgment call layered on top of ADR-4's "no auto-merge" reasoning, so confirmation stayed a pure signal.
- **Bonus feature selection**: all nine optional features (B1–B9) were implemented, in the priority order that gave the most evaluation signal per unit of time (core correctness, per §13 of the brief, came first).
- **The API Docker image installs with dev dependencies** (Pest, Pint, Faker) rather than a stripped `--no-dev` build, so `./vendor/bin/pest`, `./vendor/bin/pint` and `db:seed` work immediately for an evaluator with no extra install step. This is explicitly a development/evaluation trade-off, documented in the Scalability section of [ARCHITECTURE.md](ARCHITECTURE.md).
- **Reverse geocoding (B8)** is resolved once per report by a queued job and stored on the row (`address` column) — "cached on the server" was read as "resolved once, reused by every read," not "re-queried and cached per request."
- **PHP version**: [PLAN.md](PLAN.md) scoped PHP 8.3 at planning time; the project was actually built on PHP 8.4 (see `require.php` in [api/composer.json](api/composer.json)), the latest stable release by the time development started. PLAN.md is left as-is as a historical record of the original plan; this is the correction.

## Known limitations and what would be improved with more time

- The public map can still look visually noisy when many overlapping reports describe the same real-world outage (ADR-4); confirmation gives a corroboration signal but reports are never auto-merged.
- Mobile responsiveness relies on the layout's flex/stack utilities reflowing correctly rather than screen-specific designs beyond the navbar and hero breakpoints; a pass with real device testing would be the next hardening step.
- `NotifySubscribersOfNewOutage` and `ResolveOutageReportAddress` run synchronously during the Pest suite (`QUEUE_CONNECTION=sync` is forced in `phpunit.xml`), so the existing report-creation tests make a real network call to Nominatim rather than a mocked one; this was a deliberate trade-off to avoid destabilizing the suite under time pressure rather than an oversight — see [ARCHITECTURE.md](ARCHITECTURE.md) for the geocoding service boundary that would make this easy to fake later.
- Zone-subscription fan-out notifies every matching subscriber with one row per report; at the scale discussed in the Scalability section of [ARCHITECTURE.md](ARCHITECTURE.md), this would need to move to a batched insert.
