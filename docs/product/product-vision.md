<!-- aegis-thread doc header -->
# Product Vision

Status: Specification (draft for human review)
Date: 2026-10-24

## Purpose

This document states who AegisThread is for, which problems it solves, and
what value it delivers. It contains no implementation claims and no
retrieval-quality claims; retrieval quality is governed exclusively by the
policy in
[MVP scope: Retrieval quality policy and evaluation design](mvp-scope.md#retrieval-quality-policy-and-evaluation-design).

## Target users

Privacy-conscious teams and organizations that use AI assistance over their
own documents — for example legal, medical, research, and consulting teams
whose material they may not expose to third parties casually. Three named
user roles:

- **Knowledge worker** — asks questions over the team's document base and
  holds the conversation.
- **Team administrator / operator** — configures the deployment, manages
  retention and deletion, and is accountable for data protection.
- **Compliance or data-protection officer** — audits what is retained, what
  is deleted, and what leaves the organization's boundary.

## Problems

1. **Opaque memory usage.** AI assistants silently decide what to remember
   about users and their content; users cannot see what is retained, why an
   answer leaned on remembered material, or how to change it.
2. **Unverifiable answers.** Answers over documents arrive without checkable
   sources, so a plausible-sounding wrong answer is indistinguishable from a
   grounded one.
3. **Multi-party exposure of queries and content.** Every query can traverse
   infrastructure — relays, gateways, AI providers — where its content and
   the requester's identity can be correlated or exposed.
4. **No user control over retained AI state.** Users cannot inspect, correct,
   or delete what an assistant persists about them, and deletion rarely
   covers the artifacts derived from the retained data.

## Value proposition

1. **Memory you can see and govern.** In AegisThread, memory is not an
   invisible side effect: persistent memory exists only as explicitly
   governed constructs with a user-controlled lifecycle — candidates stay
   unapproved until a user approves them, and every persistent memory item
   can be inspected, corrected, and deleted
   ([FR-MEM](mvp-scope.md#functional-requirements)).
2. **Answers with checkable sources.** Answers must carry citations that
   resolve to evidence actually retrieved for that answer
   ([FR-CIT](mvp-scope.md#functional-requirements)); whether answers are
   good is treated as a measurable question governed by an evaluation
   procedure, not as a marketing claim.
3. **A submission path with an honest trust model.** The client-to-gateway
   submission path is OHTTP-protected with its protection scope stated
   precisely ([ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md),
   [system design: OHTTP](../architecture/system-design.md#ohttp-integration-and-trust-model)),
   tenant isolation is a day-one requirement — no cross-tenant read path
   shall exist, by design and to be verified once implementation exists —
   and outbound requests are default-deny.
4. **Control that includes derived data.** Retention is defined per state
   construct, and deletion removes the item and everything derived from it —
   embeddings, extracted text, caches, derived checkpoints
   ([system design: privacy](../architecture/system-design.md#privacy-and-data-protection-requirements)).

## Differentiators

1. **OHTTP-protected submission path.** Queries are protected at the
   client-to-gateway boundary so a forwarding relay cannot read submitted
   content, with the protection scope stated honestly
   ([system design: OHTTP integration and trust model](../architecture/system-design.md#ohttp-integration-and-trust-model);
   [ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md)).
2. **Tenant isolation as a day-one requirement.** No cross-tenant read path
   shall exist at any layer, regardless of deployment topology — by design,
   and to be verified once implementation exists
   ([system design: privacy](../architecture/system-design.md#privacy-and-data-protection-requirements)).
3. **User-controlled memory lifecycle.** Persistent memory is inspectable,
   correctable, and deletable by the user
   ([FR-MEM](mvp-scope.md#functional-requirements)).
4. **Citation integrity.** Every citation resolves to evidence actually
   retrieved for that answer
   ([FR-CIT](mvp-scope.md#functional-requirements)).

## Project goals vs. product features

**Project goals** — what motivates building AegisThread; these motivate the
work but are not product features:

- Advance applied AI engineering practice: retrieval pipelines, evaluation
  discipline, and agent runtime design with explicit boundaries.
- Advance privacy engineering practice: OHTTP integration, tenant isolation,
  and retention/deletion designed in from the start.
- Demonstrate production-grade architecture discipline: a modular monolith,
  provider-neutral and transport-neutral seams, and recorded decisions.

**Product features** — what users get; the normative definitions live in
[MVP scope](mvp-scope.md#capability-table):

- Ingest documents and ask questions over them with citation-backed answers.
- Converse with an assistant that runs a bounded agent loop over a scoped
  tool surface.
- Control persistent memory: inspect, correct, delete.
- Submit queries over an OHTTP-protected path.

## Product-level non-goals

- **No multi-tenant SaaS claims.** AegisThread makes no SaaS offering or
  claim; the deployment topology is an open decision (OQ-01 in the
  [register](../development/implementation-plan.md#open-questions-and-assumptions-register)).
- **No consumer product.** The target users are organizational roles, not
  consumers.
- **No commitment to an orchestration framework or external runtime.** How
  runtime behavior is orchestrated is an open architectural decision
  ([ADR-0006](../adr/0006-ai-runtime-orchestration-deferred.md)).
- **No AI provider lock-in.** Provider selection is open (OQ-05) and the
  integration boundary is provider-neutral by design
  ([ADR-0008](../adr/0008-provider-neutral-ai-boundary.md)).
- **No retrieval-quality or answer-quality claims of any kind.** Governed
  exclusively by the
  [evaluation policy](mvp-scope.md#retrieval-quality-policy-and-evaluation-design).

## Links

- [Glossary](glossary.md)
- [MVP scope](mvp-scope.md)
- [System design](../architecture/system-design.md)
- [Implementation plan](../development/implementation-plan.md)
- [ADR-0001](../adr/0001-modular-monolith-first.md) through [ADR-0009](../adr/0009-symfony-8x-line-and-php-runtime.md)
