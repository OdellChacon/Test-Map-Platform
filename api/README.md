# Outage Map Platform — API

This is the backend of the [Outage Map Platform](../README.md): a Laravel 13 REST API (PHP 8.4) that powers a power-outage reporting service with a live public map. It handles authentication (Laravel Sanctum, SPA cookie-based sessions), outage report CRUD and moderation, reverse geocoding, zone-subscription notifications, and broadcasts live report updates over Laravel Reverb (WebSocket).

For installation, running the full stack with Docker, demo accounts, the test suite and everything else, see the **[root README](../README.md)** — that's the single source of truth for setup instructions and this file doesn't duplicate them.

The full OpenAPI 3.0 contract is at [openapi.yaml](openapi.yaml).

## Working directly in `api/` (without Docker)

These commands assume you have PHP 8.4, Composer and a PostgreSQL+PostGIS instance available locally, and are running them from inside `api/`. If you're using the Docker setup, prefix each with `docker compose exec api` instead (see the root README).

Run the test suite (Pest, configured via `composer test`):

```bash
./vendor/bin/pest
```

or

```bash
composer test
```

Check/fix code style (Laravel Pint):

```bash
./vendor/bin/pint
```
