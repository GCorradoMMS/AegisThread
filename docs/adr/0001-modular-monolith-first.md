# ADR-0001: Initial architecture: modular monolith

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

AegisThread is in the specification phase: no implementation exists yet, the
team is small, and the first releases must prove the product rather than a
distributed-systems design. The product nevertheless carries hard
requirements — tenant isolation, per-construct retention and deletion,
auditable authorization — that need real internal structure to be testable.
The architecture must provide that structure without paying for operational
complexity that no current need justifies.

## Decision

The system is built as a **modular monolith**: one deployable application
whose internal modules (interface/transport layer; conversation and agent
runtime; ingestion; retrieval; memory; AI integration boundary; persistence;
asynchronous processing) have explicit, documented responsibilities and
in-process boundaries. Module boundaries are architectural contracts even
though the modules share one process and one deployment. The module map and
responsibilities are defined in the
[system design](../architecture/system-design.md#modules-and-responsibilities).

## Rationale

- One deployment removes distributed failure modes, cross-service data
  consistency, and multi-service observability from the critical path of
  proving the product.
- The hardest requirements of this product — isolation, retention, deletion
  including derived data — are easiest to design, test, and verify inside one
  boundary where data flow is explicit.
- **No infrastructure without concrete need** (decision D-9) is recorded
  here as a binding architectural decision: no component, service, datastore,
  or runtime is introduced unless a current, concrete, stated need requires
  it. `AGENTS.md` states this principle for daily engineering work; that file
  is engineering guidance, not the decision record — this section is the
  authoritative record of the decision.
- Extracting modules into separately deployable services stays possible;
  explicit module boundaries from day one are exactly what makes later
  extraction tractable.

## Alternatives considered

- **Microservices from day one.** Rejected: pays distributed-systems costs
  before any product evidence exists, and contradicts the
  no-infrastructure-without-need decision.
- **Multiple applications per bounded context.** Rejected for the same
  reason at smaller scale; the MVP has one deployment target.
- **Monolith without internal module boundaries.** Rejected: isolation and
  deletion requirements would become unverifiable inside an unstructured
  codebase.

## Consequences

- Module discipline must be enforced continuously — in reviews now, and by
  automated boundary checks once tooling exists (M1 in the
  [implementation plan](../development/implementation-plan.md#milestones)).
- Scaling is vertical-first; a module may be extracted only after a
  concrete, documented need and a reviewed decision.
- The persistence decision ([ADR-0002](0002-postgresql-source-of-truth.md))
  and the state-separation decision
  ([ADR-0003](0003-separated-ai-state-persistence.md)) live inside this
  single boundary.

## Links

- [ADR-0002](0002-postgresql-source-of-truth.md) — PostgreSQL as the source of truth inside the monolith
- [ADR-0003](0003-separated-ai-state-persistence.md) — separated AI state persistence
- [ADR-0005](0005-ohttp-behind-transport-abstraction.md) — transport abstraction at the boundary
- [ADR-0008](0008-provider-neutral-ai-boundary.md) — AI integration boundary
- [System design: System overview](../architecture/system-design.md#system-overview)
- [Implementation plan: Milestones](../development/implementation-plan.md#milestones)
- [Glossary: Modular monolith](../product/glossary.md#modular-monolith)
