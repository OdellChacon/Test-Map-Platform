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

**Estimated vs. actual hours.** Total actual time on the core scope (M0-M14) was ~32.0h against the 28.5h stated as the core total in §3 — about 12% over. Hours were not logged with a time tracker; the per-milestone "Actual" column below is reconstructed from memory, so it is approximate, but it reflects genuine variance milestone to milestone rather than a split of the total in fixed proportion to the estimate.

Writing this section also surfaced an arithmetic error in §3 itself: the 15 individual milestone estimates actually sum to 31.5h, not the 28.5h stated as the "Core total" (and repeated in the §4 risk row). Per the brief, the original plan is not rewritten after the fact, so §3 and §4 are left as originally committed, bug included; this paragraph is the correction. Measured against the real 31.5h sum of the individual estimates instead, the ~32.0h actual is only about 2% over.

| # | Milestone | Estimate | Actual |
|---|---|---|---|
| M0 | Planning and architecture | 2.0h | 1.5h |
| M1 | Infrastructure | 2.5h | 2.0h |
| M2 | Backend foundations | 2.0h | 2.5h |
| M3 | Reports API | 3.0h | 3.5h |
| M4 | Abuse prevention | 1.5h | 3.0h |
| M5 | Expiration | 2.0h | 1.5h |
| M6 | Public API | 2.0h | 3.0h |
| M7 | Frontend shell | 2.5h | 2.0h |
| M8 | Frontend auth flows | 1.5h | 3.0h |
| M9 | Map adapter and report creation | 3.0h | 1.5h |
| M10 | My reports and public map | 2.5h | 1.0h |
| M11 | Backend tests | 3.0h | 3.0h |
| M12 | API docs and linting | 1.0h | 2.0h |
| M13 | Documentation | 2.0h | 1.5h |
| M14 | Clean-install verification | 1.0h | 1.0h |
| | **Total** | **28.5h*** | **32.0h** |

*Sum of the rows above is actually 31.5h — see the arithmetic-error note above.

Milestone order was deviated from deliberately partway through: M7-M10 (frontend) were pulled forward ahead of M5-M6 and M11-M12 so that visible, clickable progress existed before the backend-only milestones were finished. This traded strict ordering for earlier feedback and was the right call for this project, but it means M3/M4 (reports API, abuse prevention) were technically "done" before M5/M6 (expiration, public endpoint) even though the numbering suggests otherwise; the final commit history reflects the actual order work happened in, not the numbering above.

One real mistake surfaced by M11: the first test runs executed against the development database instead of an isolated test database, because Docker's `env_file` injects `.env` as real OS environment variables inside the container, which silently takes priority over `phpunit.xml`'s `<env>` overrides unless each one is marked `force="true"`. Laravel's `RefreshDatabase` trait ran `migrate:fresh` against that connection on its first use, which dropped every table in the development database, including the seeded demo account and manually-created test data. Fixed by forcing the test environment variables (both `<env force="true">` and `<server force="true">`, since Laravel's env resolution checks `$_SERVER` ahead of `putenv`-based values) and by re-seeding afterward. With more time, this is exactly the kind of thing a from-scratch project should catch before ever running a destructive command against a connection it hasn't explicitly verified — in hindsight, printing `config('database.connections.pgsql.database')` once, before the first real test run, would have caught it immediately instead of after the fact.

What would be done differently with more time: at the point this paragraph was first written, the plan was to implement the bonus WebSocket push for the live map and the confirmation flow as future work. Both were in fact implemented afterward (B1 and B2, see ADR-7 and ADR-4), along with the remaining seven bonus features (B3–B9, all described in `README.md`). With still more time beyond that, the next things worth doing are: merging or grouping duplicate reports instead of only surfacing a confirmation count (ADR-4's trade-off), a proper device-tested mobile pass, and moving `NotifySubscribersOfNewOutage`'s per-subscriber writes to a batched insert at scale (see the Scalability section of `ARCHITECTURE.md`).
