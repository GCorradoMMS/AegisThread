<!-- aegis-thread doc header -->
# AegisThread

Status: M1 skeleton implemented; specification documents remain the authority for what comes next
Date: 2026-10-25

AegisThread is a Symfony/PHP application for privacy-conscious,
retrieval-based AI assistance. The specification documents below define
the product and architecture; the first application skeleton (M1) is
implemented — see [Implementation status](#implementation-status).

## Documentation index

| Document | Purpose | Audience |
|---|---|---|
| [Product vision](docs/product/product-vision.md) | Who this is for, the problems it solves, the value proposition | Everyone |
| [MVP scope](docs/product/mvp-scope.md) | Capabilities, measurable criteria, requirements, evaluation policy | Product and engineering |
| [System design](docs/architecture/system-design.md) | Modules, responsibilities, architecture diagrams, technology stack | Engineering |
| [Implementation plan](docs/development/implementation-plan.md) | Milestones, decision traceability, open-questions register | Engineering and the project owner |
| [Setup guide](docs/development/setup.md) | Dev environment, env vars, checks | Engineering |
| [Glossary](docs/product/glossary.md) | The single home for term definitions | Everyone |
| [ADRs](docs/adr/0001-modular-monolith-first.md) | Architecture decision records 0001–0009 | Engineering |

## Implementation status

The M1 skeleton is implemented:

- Minimal Symfony 8.1 backend: health endpoint and configuration report;
  Dockerized PostgreSQL 18 with the pgvector extension provisioned;
  versioned schema migrations.
- Provider-neutral AI and OHTTP transport seam interfaces with no adapters
  behind them ([ADR-0008](docs/adr/0008-provider-neutral-ai-boundary.md),
  [ADR-0005](docs/adr/0005-ohttp-behind-transport-abstraction.md)).
- Minimal Next.js 16 frontend demonstrating backend integration
  (healthy / degraded / unavailable states).
- Docker Compose development stack with health checks, all services bound
  to 127.0.0.1.
- Quality baselines: PHPUnit, PHPStan, PHP-CS-Fixer, ESLint/TypeScript;
  the documentation validation suite (V1–V18); GitHub Actions CI.

Not implemented yet: authentication — the security baseline is an open
decision ([OQ-16](docs/development/implementation-plan.md#open-questions-and-assumptions-register));
no AI provider, no RAG, no agent runtime, and no OHTTP flow.

## Quickstart

```console
$ docker compose up -d --build
$ docker compose ps    # wait until all services report (healthy)
# frontend: http://127.0.0.1:3000
# backend:  http://127.0.0.1:8081
$ docker compose down
```

The full environment-variable table and every local check are documented
in the [setup guide](docs/development/setup.md).

## Engineering process

Engineering conventions, agent workflow, and review rules live in
[AGENTS.md](AGENTS.md) and in the documents under `docs/engineering/`.
