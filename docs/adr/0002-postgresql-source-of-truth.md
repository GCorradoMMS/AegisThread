# ADR-0002: PostgreSQL is the source of truth

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

AegisThread durably holds several kinds of product state: ingested documents
and their content, the memory constructs
([ADR-0003](0003-separated-ai-state-persistence.md)), conversation history,
workflow checkpoints, and derived artifacts (extracted text, embeddings,
caches). Each of these could tempt a separate specialized store — a document
store, a vector database, a key-value cache. Every additional system of
record multiplies the places where isolation, retention, and deletion must be
proven correct.

## Decision

All durable product state is stored in **PostgreSQL**, which is the single
system of truth for AegisThread. Specifically:

- Source data — documents, the memory constructs, conversation history, and
  workflow checkpoints — lives in PostgreSQL.
- There is no second system of record for any of it.
- Derived artifacts (embeddings, extracted text, caches, derived checkpoints)
  may exist to serve performance, but every derived artifact is
  **rebuildable from PostgreSQL-resident source data** and is never
  authoritative.
- **pgvector** is recorded as a *Proposed* capability for vector retrieval
  inside PostgreSQL, pending confirmation during M2 storage design
  ([implementation plan: Milestones](../development/implementation-plan.md#milestones));
  embedding dimensions additionally depend on OQ-05 in the
  [register](../development/implementation-plan.md#open-questions-and-assumptions-register).
  No vector store outside PostgreSQL is introduced.

## Rationale

- One transactional boundary is the cheapest way to make tenant isolation and
  deletion-including-derived-data verifiable; privacy and data protection are
  day-one architectural concerns
  ([system design: privacy](../architecture/system-design.md#privacy-and-data-protection-requirements)).
- Deletion semantics need a single authority: if two stores each claim to
  hold the truth, proving "deleted everywhere" requires cross-store
  coordination on every deletion path.
- Backup, restore, and audit remain single-store operations, consistent with
  no-infrastructure-without-need
  ([ADR-0001, Rationale](0001-modular-monolith-first.md#rationale)).
- The rebuildability rule keeps performance artifacts from silently becoming
  systems of record.

## Alternatives considered

- **Polyglot persistence** (dedicated vector database plus document store
  plus relational core). Rejected for the MVP: multiplies isolation and
  deletion proofs and the operational surface without a demonstrated concrete
  need.
- **Object storage as the system of record, with database indexes.**
  Rejected: splits transactional truth across two systems; revisit only if
  document sizes or access patterns become a concrete need.
- **A separate vector database for retrieval.** Rejected while pgvector is a
  viable *Proposed* capability; changing this requires a reviewed decision
  recorded in a new ADR.

## Consequences

- Database schema migrations are the contract for state evolution; they must
  be reversible where the framework supports it.
- Every derived store must have a documented rebuild path, and deletion
  cascades to derived data.
- Large-document storage inside PostgreSQL must be designed deliberately in
  M2 rather than assumed.
- If pgvector proves insufficient during evaluation, the change is a reviewed
  decision recorded in a new ADR, not an ad-hoc addition.

## Links

- [ADR-0001](0001-modular-monolith-first.md) — the modular monolith boundary
- [ADR-0003](0003-separated-ai-state-persistence.md) — the distinct state domains inside PostgreSQL
- [System design: Privacy and data protection requirements](../architecture/system-design.md#privacy-and-data-protection-requirements)
- [Technology stack](../architecture/system-design.md#technology-stack)
- [Glossary: Source of truth](../product/glossary.md#source-of-truth)
- [Open questions register](../development/implementation-plan.md#open-questions-and-assumptions-register)
