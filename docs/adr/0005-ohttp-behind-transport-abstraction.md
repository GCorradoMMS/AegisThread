# ADR-0005: OHTTP integration sits behind an application-facing transport abstraction

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

The verified candidate PHP library for OHTTP is `gcorradomms/ohttphp`. As of
2026-10-24 it is published only from its development branch (no tagged
release exists), the repository holds a single commit, and its HPKE
dependency is at a 0.x version (facts and sources:
[technology stack](../architecture/system-design.md#technology-stack)).
Building application code directly against such an API would couple
AegisThread's protected submission path to an unstable, single-maintainer
surface.

## Decision

Application code depends **only on an application-owned, application-facing
transport abstraction** — operations to encapsulate a request, decapsulate a
response, and forward through a relay — and never on the `ohttphp` API
directly. The library API appears exclusively inside an adapter behind that
abstraction. The abstraction is the only place where pinning, replacing, or
forking the library has consequences.

## Rationale

- The verified supply-chain posture (release-less, single commit, 0.x
  dependency) makes one of three responses likely: pin to an exact commit,
  replace the library, or fork it. All three are local changes behind the
  seam and require no application-code churn.
- This is dependency inversion applied at a transport seam, consistent with
  the AI integration boundary
  ([ADR-0008](0008-provider-neutral-ai-boundary.md)) and the modular
  monolith ([ADR-0001](0001-modular-monolith-first.md)).
- The OHTTP scope decision
  ([ADR-0004](0004-ohttp-scope-client-to-gateway.md)) stays
  library-independent: the trust model is ours; the implementation is
  pluggable.

## Alternatives considered

- **Use the library API throughout the application.** Rejected: couples the
  product to an unstable surface and turns the OQ-03/OQ-15 contingencies
  into application-wide refactors.
- **Implement OHTTP in-house now.** Rejected: cryptographic protocol
  implementation is a cost and risk without a concrete need while a candidate
  library exists for evaluation.
- **Block all OHTTP work until a stable release exists.** Rejected: M5 needs
  a seam regardless; the seam enables evaluation without commitment.

## Consequences

- One seam interface and one adapter must be designed and tested in M5
  ([implementation plan: Milestones](../development/implementation-plan.md#milestones)).
- The seam must not leak library types; its contract is expressed in
  application terms.
- Adoption is gated on the pin strategy and contingency decisions (OQ-03,
  OQ-15); the milestone reflects that gate.

## Links

- [ADR-0004](0004-ohttp-scope-client-to-gateway.md) — scope and trust model of the protected boundary
- [System design: OHTTP integration and trust model](../architecture/system-design.md#ohttp-integration-and-trust-model)
- [Technology stack](../architecture/system-design.md#technology-stack) — verified library facts
- [Open questions register](../development/implementation-plan.md#open-questions-and-assumptions-register) — OQ-03, OQ-15
- [Glossary: OHTTP](../product/glossary.md#ohttp)
