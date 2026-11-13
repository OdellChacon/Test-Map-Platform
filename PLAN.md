## 1. Overview

This document is committed before any application code, as required by the test brief. It breaks the work into ordered milestones, estimates effort per milestone, and lists the risks identified up front. The Retrospective section at the end is written after delivery and is never used to rewrite the estimates above it.

## 2. Confirmed Stack Decisions

| Layer | Choice |
|---|---|
| Backend | PHP 8.3, Laravel 13, REST API only |
| API auth | Laravel Sanctum, SPA cookie-based session |
| Frontend | Vue 3, Vue Router, Pinia |
| Database | PostgreSQL 16 with PostGIS |
| Cache / queues | Redis |
| Map provider | OpenStreetMap tiles via Leaflet |
| Containers | Docker, Docker Compose |

Full justification for each choice is recorded as an ADR in `ARCHITECTURE.md`, not repeated here.

## 3. Milestones

| # | Milestone | Deliverable | Estimate |
|---|---|---|---|
| M0 | Planning and architecture | `PLAN.md`, initial `ARCHITECTURE.md` (diagram, ERD, API contract, ADR stubs) | 2.0h |
| M1 | Infrastructure | `docker-compose.yml`, Dockerfiles, `.env.example` files, health checks, one-command startup of empty apps | 2.5h |
| M2 | Backend foundations | Laravel install, Sanctum config, `User` and `OutageReport` models, migrations, factories, seeders | 2.0h |
| M3 | Reports API | CRUD endpoints, Form Requests, Policies, API Resources, status enum, pagination on "my reports" | 3.0h |
| M4 | Abuse prevention | Per-user rate limiting on report creation, active-report cap, auth endpoint rate limiting, configurable via env | 1.5h |
| M5 | Expiration | Scheduler command, expiration job, 72h safety cap for "until closed" reports, expiry enforced at query time independent of job timing | 2.0h |
| M6 | Public API | Bounding-box query endpoint, PostGIS spatial index, resource with zero personal data | 2.0h |
| M7 | Frontend shell | Vue app shell, router, Pinia store, single HTTP service layer, route guards, design tokens file | 2.5h |
| M8 | Frontend auth flows | Register, login, logout, loading/error/empty states | 1.5h |
| M9 | Map adapter and report creation | Leaflet encapsulated behind an adapter component, visual point selection, radius preview, create-report form | 3.0h |
| M10 | My reports and public map | Paginated report list, edit/close own report, public map on landing and dedicated view, periodic polling | 2.5h |
| M11 | Backend tests | Pest coverage for auth, validation, ownership, rate limiting, expiration (including 72h cap), public data leak | 3.0h |
| M12 | API docs and linting | OpenAPI spec, Laravel Pint clean run | 1.0h |
| M13 | Documentation | Finalized `ARCHITECTURE.md` (security, scalability, all ADRs), `README.md` | 2.0h |
| M14 | Clean-install verification | Fresh clone, follow own README, fix anything broken | 1.0h |
| | **Core total** | | **28.5h** |

Bonus features (section 10 of the brief) are only attempted after M0–M14 are complete and verified, and are tracked separately rather than estimated here.

## 4. Risks and Mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Core estimate (28.5h) exceeds the suggested 16-24h range | Deadline pressure late in the week | Treat M0-M6 and M11 (backend, tests, correctness) as non-negotiable; if time runs short, trim frontend polish (M9-M10) before trimming expiration correctness or tests |
| Scheduler delay leaving an expired report visible as active | Core correctness requirement, explicitly checked | Expiration is enforced twice: a scheduled job flips status proactively, and every read query additionally filters by computed expiry time, so a late job cannot cause a stale "active" result |
| PostGIS query performance for bounding-box filtering | Public endpoint is the most frequently hit, unauthenticated | Store geometry in a native PostGIS `geography` column with a GiST index from the first migration, not retrofitted later |
| Duplicate outage reports in the same area | No single correct answer, must be justified | Decision is deferred to `ARCHITECTURE.md` as ADR-4 before M3 is implemented, so the data model already reflects the chosen approach |
| Leaflet API leaking into screens/components | Couples the whole frontend to one map provider | All map interaction goes through one adapter component from M9 onward; screens never import Leaflet directly |
| Running out of time for bonus features | Lost points, but non-blocking | Bonus is only started after M14 is verified complete; if skipped, it is stated plainly in the README rather than attempted partially |

## 5. Retrospective

To be completed after delivery, comparing the estimates above against actual time spent per milestone, and noting what would be done differently with more time.
