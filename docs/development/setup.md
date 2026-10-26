<!-- aegis-thread doc header -->
# Setup

Status: Current (M1 — describes the development stack as implemented)
Date: 2026-10-25

This document describes the M1 development environment: how to start the
Docker Compose stack, which URLs and environment variables it uses, and
which checks a developer runs locally. The
[implementation plan](implementation-plan.md) remains the authority for
what M1 delivers.

## Prerequisites

- Docker Engine with Compose v2 (`docker compose version` must work):
  it runs the whole stack.
- The frontend checks additionally need Node.js with npm on the host
  (CI uses Node 26). PHP and Composer are never needed on the host —
  every backend check runs inside the backend image.

## Quickstart

```console
$ docker compose up -d --build
$ docker compose ps                 # wait until postgres, backend, and
                                    # frontend all report (healthy)
$ curl -fsS http://127.0.0.1:8081/api/health
$ docker compose down               # add -v to also delete the data volume
```

Copying the root `.env.example` to `.env` is **optional**. Exact behavior:

- Without a root `.env` file, `compose.yaml` interpolates its baked-in
  fallback values (`aegisthread` for database name, user, and password).
  A clean clone therefore boots without any setup.
- To customize `POSTGRES_DB`, `POSTGRES_USER`, or `POSTGRES_PASSWORD`,
  create `.env` **before the first boot**. The PostgreSQL image applies
  these variables only when its data directory is empty; the named volume
  `postgres_data` keeps earlier values, so changing them after a first
  boot has no effect until the volume is recreated with
  `docker compose down -v` (this deletes all local stack data).
- Constraint: `POSTGRES_PASSWORD` must not contain characters that break
  compose interpolation (`` $ ` ``) or URL parsing (`: / @ ? # [ ] %`),
  because it is embedded in `DATABASE_URL` strings by consumers
  (see [.env.example](../../.env.example)).

## Services and URLs

| Service | URL | Notes |
|---|---|---|
| frontend | http://127.0.0.1:3000 | Next.js; renders the backend status (healthy / degraded / unavailable) |
| backend | http://127.0.0.1:8081 | Symfony; `GET /api/health`, `GET /api/configuration` |
| postgres | 127.0.0.1:5432 | PostgreSQL 18 + pgvector 0.8.7 (image `pgvector/pgvector:0.8.7-pg18`) |

All three ports are bound to `127.0.0.1` only (the backend's host port is
8081; the container port remains 8080). The skeleton MUST NOT be exposed
beyond loopback: no authentication exists yet — the security baseline is
an open decision
([OQ-16](implementation-plan.md#open-questions-and-assumptions-register)) —
and no network controls exist. Until both are in place, loopback-only
binding is the only permitted exposure; this document states no firewall
or network-policy claims beyond the compose binding itself.

### Before any non-loopback exposure

A requirements checklist — not a claim that any of it exists yet:

- An operator-auth decision for the two diagnostic endpoints
  ([OQ-16](implementation-plan.md#open-questions-and-assumptions-register)),
  and a re-assessment of what they disclose.
- `APP_ENV=prod` and an explicit `APP_DEBUG=0`.
- A production runtime instead of `php -S`.
- A per-environment `APP_SECRET` from the real environment or a vault —
  never a committed value.
- Database least privilege: a runtime role separate from the migration
  superuser, with extension provisioning by an admin.
- Digest-pinned images instead of version tags.
- An actual network control — the compose loopback binding is not one.

## Environment variables

This table is the canonical inventory for the M1 skeleton. "Scope" is
where the variable is read.

| Variable | Scope | Purpose | Default | Secret? |
|---|---|---|---|---|
| `POSTGRES_DB` | root `.env` → compose (postgres service) | Database created on first boot | `aegisthread` | no |
| `POSTGRES_USER` | root `.env` → compose (postgres service) | Superuser created on first boot | `aegisthread` | no |
| `POSTGRES_PASSWORD` | root `.env` → compose (postgres service) | Password for that superuser; interpolated into consumers' DSNs | `aegisthread` | yes — dev-only value; never reuse outside the local stack |
| `DATABASE_URL` | backend (Symfony) | PostgreSQL DSN; the source of truth per [ADR-0002](../adr/0002-postgresql-source-of-truth.md). The DSN deliberately carries no `serverVersion` — it is pinned in `config/packages/doctrine.yaml` | `pdo-pgsql://aegisthread:aegisthread@postgres:5432/aegisthread?connect_timeout=2&charset=utf8` (baked from [backend/.env.example](../../backend/.env.example)) | contains credentials — dev-only here; required by the boot guard |
| `APP_ENV` | backend (Symfony) | Kernel environment: `dev`, `test`, or `prod` | `dev` | no |
| `APP_SECRET` | backend (Symfony) | Framework secret; required by the boot guard | `dev_only_not_a_secret_change_me` | yes — must be replaced with a generated value in every real environment |
| `APP_DEBUG` | backend (Symfony) | Debug mode (detailed error pages). Explicit in `backend/.env.example` because Symfony would otherwise silently default it to enabled outside `prod` — a deliberate loopback-only dev choice (see the checklist below) | `1` | no — but it must be `0` before any non-loopback exposure |
| `DEFAULT_URI` | backend (Symfony) | Base URI for URL generation in non-HTTP (CLI) contexts (`framework.router.default_uri`) | `http://localhost:8080` | no |
| `BACKEND_BASE_URL` | frontend (Next.js server process) | Base URL the server uses to fetch `/api/health`; server-only — never prefix with `NEXT_PUBLIC_` | `http://localhost:8081` (code fallback; the compose stack sets `http://backend:8080`) | no |

Notes:

- [backend/.env.dev](../../backend/.env.dev) carries a
  Symfony-skeleton-convention, dev-only `APP_SECRET` (it applies when
  running the app on the host in the `dev` environment; it is not baked
  into the image). Real deployments must override `APP_SECRET` with a
  generated secret regardless.
- Real environment variables always win over values from `.env` files
  (Symfony's dotenv never overrides the process environment).

## Running checks

Where each check runs:

| Check | Where | Command |
|---|---|---|
| Documentation validation (V1–V18) | host, repository root | `bash tools/validate-docs.sh` |
| Backend static analysis | inside the backend image | `docker compose run --rm backend composer stan` (run the cache warmup below first) |
| Backend code style (fix / check) | inside the backend image | `docker compose run --rm backend composer cs-fix` / `composer cs-check` |
| Backend default test suite | inside the backend image | see below — requires an **unreachable** database |
| Backend database tests | inside the backend image | `docker compose run --rm backend vendor/bin/phpunit --group requires-db` (requires the live postgres of the stack) |
| Frontend lint / typecheck / build | host, in `frontend/` | `npm ci`, then `npm run lint`, `npm run typecheck`, `npm run build` |

Backend details:

```console
# Static analysis. One container for warmup + analysis: PHPStan reads the
# Symfony dev container that the warmup produces, and containers started by
# `docker compose run` are ephemeral — separate invocations would discard
# the warmed cache before the analysis reads it.
$ docker compose run --rm backend \
    sh -c 'php bin/console cache:warmup --env=dev && composer stan'

# Default suite. The -e override points DATABASE_URL at a closed port on
# purpose: the default suite excludes the `requires-db` group and two of
# its contract tests assert database-unavailable behavior — against a
# reachable database they fail BY DESIGN.
$ docker compose run --rm \
    -e DATABASE_URL='pdo-pgsql://aegisthread:aegisthread@127.0.0.1:59999/aegisthread?connect_timeout=2' \
    backend composer test

# Live-database suite (stack must be up; the one-shot `migrate` service has
# then already applied the migrations). One-time fixture: the tests connect
# to the suffixed test database (<db>_test) — create it once per volume:
$ echo "SELECT 'CREATE DATABASE aegisthread_test' WHERE NOT EXISTS \
    (SELECT FROM pg_database WHERE datname = 'aegisthread_test')\gexec" \
    | docker compose exec -T postgres psql -U aegisthread -d aegisthread
$ docker compose run --rm backend vendor/bin/phpunit --group requires-db
```

## Dev-grade caveats

- The backend serves through `php -S`, PHP's single-threaded development
  server: one request at a time. It exists to make the skeleton runnable,
  not to serve concurrent traffic.
- The backend image includes the development dependencies (PHPUnit,
  PHPStan, PHP-CS-Fixer) and runs the dev server as its default command.
  It is a development image, not a deployment artifact.
- The backend container healthcheck accepts both HTTP 200 (healthy) and
  503 (degraded — for example, database down) as proof of liveness: it
  answers "is the backend process alive", not "is every dependency
  healthy". Dependency state is visible in the `/api/health` body and in
  the frontend rendering. The rationale is documented in the
  [compose.yaml](../../compose.yaml).
- Stack data (including all database contents) lives in the named volume
  `postgres_data`; `docker compose down -v` deletes it.

## Architecture pointers

- `backend/` — Symfony application, layered as `src/Domain` (no framework
  dependencies), `src/Application` (use cases), `src/Infrastructure`
  (adapters), `src/Interface` (HTTP controllers).
- `frontend/` — Next.js client; `tools/` — the documentation validation
  suite.
- The two application-owned domain seams, each currently an interface with
  no adapter behind it: `src/Domain/Ai` (`ModelGateway`,
  `EmbeddingGateway` — [ADR-0008](../adr/0008-provider-neutral-ai-boundary.md))
  and `src/Domain/Ohttp` (`OhttpTransport` —
  [ADR-0005](../adr/0005-ohttp-behind-transport-abstraction.md)).
- Database schema changes live as versioned Doctrine PHP migrations in
  `backend/migrations/`; the one-shot `migrate` compose service applies
  them (`--all-or-nothing`) on every stack start.

## Links

- [Implementation plan](implementation-plan.md)
- [System design](../architecture/system-design.md)
- [Glossary](../product/glossary.md)
