# ADR-0003: Separate persistence for semantic memory, workflow checkpoints, and conversation history

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

An AI assistant naturally generates several kinds of state that look
superficially similar because all of them originate in conversations: facts
worth remembering, execution state of long-running tasks, and the raw
transcript. Treating them as one undifferentiated store is a common accident:
it entangles retention obligations, makes user-facing memory rights
ambiguous, and turns every deletion into a cross-domain investigation.

## Decision

**Semantic memory, workflow checkpoints, and conversation history are
distinct data domains.** Each has its own purpose, lifecycle, retention
rules, and storage representation. They are never interchangeable, and no
data moves between them without an explicit reviewed decision. The
authoritative construct definitions — five constructs in total, including
the split of semantic memory into episodic and long-term approved memory —
live in the
[system design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs).

## Rationale

- The constructs carry different obligations: short-term context is
  ephemeral, episodic memory is unapproved candidate data, long-term approved
  memory is user-approved and persistent, checkpoints are resumability
  state, and history is a verbatim record. A merged store cannot express
  that in its schema, its queries, or its deletion paths.
- **Unifying later is cheap; splitting after data exists is not.** Merging
  separate stores later is a straightforward migration; un-breaking coupled
  schemas and queries after real data exists requires risky migrations under
  correctness obligations that privacy and deletion law make non-negotiable.
- User rights — inspect, correct, delete persistent memories ([FR-MEM in the
  MVP scope](../product/mvp-scope.md#functional-requirements)) — apply per
  construct with different scope; separation makes those rights verifiable.

## Alternatives considered

- **One conversation store with type tags.** Rejected: tags do not enforce
  distinct retention, deletion, or governance; the coupling returns through
  every query.
- **Memory derived transparently from conversation history.** Rejected:
  blurs approved versus unapproved data and makes "what do you remember
  about me" unanswerable by inspection.
- **Checkpoints as ordinary history entries.** Rejected: execution state has
  resumability semantics that would pollute the verbatim transcript record.

## Consequences

- More schemas and repositories than a merged design — accepted as the cost
  of verifiable lifecycle governance.
- Cross-construct features (for example, context assembly over all five
  constructs) read through explicit interfaces; there is no implicit reuse.
- Retention values and deletion service levels are per construct and remain
  open decisions (OQ-11 in the
  [register](../development/implementation-plan.md#open-questions-and-assumptions-register)).

## Links

- [ADR-0002](0002-postgresql-source-of-truth.md) — all three domains live inside PostgreSQL
- [System design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs) — authoritative definitions
- [MVP scope: FR-MEM](../product/mvp-scope.md#functional-requirements) — user rights over persistent memory
- [Glossary](../product/glossary.md) — construct terms
- [Open questions register](../development/implementation-plan.md#open-questions-and-assumptions-register)
