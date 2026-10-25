# ADR-0008: Provider-neutral AI integration boundary

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

AegisThread needs model and embedding capabilities, but no AI provider has
been selected (OQ-05 in the
[register](../development/implementation-plan.md#open-questions-and-assumptions-register)),
and provider interfaces differ in contracts, data handling, and retention
behavior. Coupling the application to one provider's interface would make the
privacy posture provider-shaped and the provider choice effectively
irreversible.

## Decision

All model and embedding access goes through an **application-owned,
provider-neutral integration boundary** — an inward-facing seam with
operations such as completing a model request and computing embeddings. No
provider is named as chosen anywhere in this corpus; provider and
embedding-model selection is tracked as OQ-05. Provider-specific code may
exist only in adapters behind this boundary.

## Rationale

- Provider choice stays reversible: candidates can be evaluated behind one
  seam without application churn.
- Privacy-relevant terms (retention, training usage, logging) must be
  recorded where data leaves the application, and providers whose terms are
  incompatible with product requirements must be rejected — the boundary,
  not the provider SDK, is that control point.
- This is the same dependency-inversion pattern as the transport seam
  ([ADR-0005](0005-ohttp-behind-transport-abstraction.md)) and the modular
  monolith ([ADR-0001](0001-modular-monolith-first.md)).

## Alternatives considered

- **Direct provider SDK usage in the runtime.** Rejected: couples the
  application and its privacy posture to one vendor's interface.
- **Multiple parallel provider integrations from day one.** Rejected:
  speculative infrastructure without a current, concrete need; the seam makes
  later adapters cheap instead.

## Consequences

- One seam to design in M1 and consume from M3
  ([implementation plan: Milestones](../development/implementation-plan.md#milestones)).
- Provider-specific features must be justified against the seam; silent
  provider assumptions are defects.
- When OQ-05 resolves, the provider's privacy terms are recorded at the
  boundary and reviewed by the security reviewer.

## Links

- [ADR-0006](0006-ai-runtime-orchestration-deferred.md) — the orchestration decision that consumes this boundary
- [ADR-0007](0007-mcp-capability-boundary.md) — the tool capability surface
- [System design: AI integration boundary](../architecture/system-design.md#ai-integration-boundary)
- [System design: Technology stack](../architecture/system-design.md#technology-stack)
- [Glossary: Provider-neutral boundary](../product/glossary.md#provider-neutral-boundary)
