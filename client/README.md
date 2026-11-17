# Outage Map Platform — Client

This is the frontend of the [Outage Map Platform](../README.md): a Vue 3 SPA (Composition API, Vue Router, Pinia) that renders the live public outage map (Leaflet + OpenStreetMap tiles, with marker clustering), lets users report and manage outages, and shows real-time updates over Laravel Echo/Reverb.

For installation, running the full stack with Docker, demo accounts and everything else, see the **[root README](../README.md)** — that's the single source of truth for setup instructions and this file doesn't duplicate them.

## Working directly in `client/` (without Docker)

These commands assume you have Node (`^22.18.0` or `>=24.12.0`, per `package.json`'s `engines`) and run `npm install` first, from inside `client/`. If you're using the Docker setup, prefix each with `docker compose exec client` instead (see the root README).

Dev server with hot reload:

```bash
npm run dev
```

Production build:

```bash
npm run build
```

Lint (oxlint + eslint, both with `--fix`):

```bash
npm run lint
```

Unit tests (Vitest + `@vue/test-utils`, jsdom):

```bash
npm run test
```
