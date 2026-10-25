<!-- aegis-thread doc header -->
# MVP Scope

Status: Specification (draft for human review)
Date: 2026-10-24

## Purpose and meaning of MVP

This document defines the minimum viable product: the smallest coherent set
of capabilities that demonstrates the AegisThread promise end to end for a
single tenant, with the privacy architecture present from the first commit —
not a prototype that adds privacy later. Every MVP capability below carries a
measurable acceptance criterion.

**Normative rule:** every criterion in this document must contain a
measurable operator or quantity — a count, a latency, a percentage, or a
directly verifiable behavior. A criterion phrased only with adjectives is
invalid and must be rewritten before implementation.

Tenancy and data isolation are defined in the
[system design: privacy section](../architecture/system-design.md#privacy-and-data-protection-requirements),
not here.

## Capability table

| ID | Capability | Description | Measurable acceptance criterion | Design section |
|---|---|---|---|---|
| C-1 | Document ingestion | Documents enter through the submission path and become retrievable within the submitting tenant. | A submitted document becomes retrievable within 5 minutes at P95 for documents up to 50 MB, or the submitter receives an ingestion failure with a reason code; and 100% of test requests by one user for another user's document (and its derived artifacts) are denied and recorded. | [Ingestion and RAG](../architecture/system-design.md#document-ingestion-and-rag) |
| C-2 | Retrieval-augmented answering with citations | Questions over ingested documents are answered with citations attached to retrieved evidence. | 100% of answers in evaluation runs contain at least one citation, and 100% of citations resolve to evidence actually retrieved for that answer (FR-CIT). | [Ingestion and RAG](../architecture/system-design.md#document-ingestion-and-rag) |
| C-3 | Conversation with bounded agent loop | Conversations run a bounded agent loop with a hard, application-enforced iteration limit. | 100% of conversation turns terminate within the configured maximum iteration count (default 8); the bound is enforced by the application and exceeding it produces a defined user-visible outcome recorded in the trace. | [Conversation and bounded agent loop](../architecture/system-design.md#conversation-and-bounded-agent-loop-mcp-interaction) |
| C-4 | MCP tool surface | Tools are reachable only through the scoped MCP surface with per-invocation authorization. | 100% of tool invocations in test runs carry an application authorization decision (allow or deny) made independently of model output and recorded in the audit log. | [Conversation and bounded agent loop](../architecture/system-design.md#conversation-and-bounded-agent-loop-mcp-interaction) |
| C-5 | Memory lifecycle | Users inspect, correct, and delete their own persistent memory items. | A user can list, correct, and delete their persistent memory items; a deletion completes within 24 hours (provisional MVP test bound; per-construct retention periods and operator-facing deletion SLAs are governed by [OQ-11](../development/implementation-plan.md#open-questions-and-assumptions-register)) and a post-deletion inspection shows the item and all data derived from it removed; 100% of test requests by one user for another user's memory item are denied and recorded. | [Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs) |
| C-6 | Asynchronous processing | Long-running work executes on workers outside the request path. | A worker outage of up to 60 minutes causes zero task loss (tasks remain durably enqueued and are redelivered after the outage; the failure transport covers terminal failures — see FR-ASYNC); retry count and backoff are operator-configurable. | [Asynchronous processing](../architecture/system-design.md#asynchronous-processing) |
| C-7 | OHTTP-protected submission path | Client submission traffic reaches the gateway only as OHTTP-encapsulated requests. | 100% of submission endpoints accept only OHTTP-encapsulated requests in integration tests; a non-encapsulated submission request receives a uniform error and never reaches application processing. | [OHTTP integration and trust model](../architecture/system-design.md#ohttp-integration-and-trust-model) |
| C-8 | Minimum operational visibility | Operators can see health and effective configuration. | The deployment exposes a health endpoint answering within 1 second and a configuration report listing every runtime-relevant setting. | [Modules and responsibilities](../architecture/system-design.md#modules-and-responsibilities) |

## Functional requirements

Each requirement is one normative sentence with a stable identifier.

- **FR-ING** — The system shall ingest user-submitted documents, store them
  as source data in the source of truth, and make their content retrievable
  only within the submitting tenant boundary.
- **FR-RAG** — Answers to document questions shall be produced by retrieving
  candidate evidence and assembling the answer from that evidence, with
  citations attached according to FR-CIT.
- **FR-CIT** — Every citation in an answer must be verifiable against
  evidence actually retrieved for that answer; model-invented references must
  never appear, measured as 100% of citations in evaluation runs resolving to
  retrieved evidence.
- **FR-CON** — Conversations shall run a bounded agent loop whose iteration
  limit is enforced by the application and whose tool use goes exclusively
  through the MCP surface under FR-MCP.
- **FR-MCP** — Every tool invocation shall pass an application-side
  authorization decision made independently of model output, as defined in
  [ADR-0007](../adr/0007-mcp-capability-boundary.md).
- **FR-MEM** — Every user shall be able to inspect, correct, and delete each
  persistent memory item derived from that user's activity, where deletion
  removes the item and all data derived from it, including embeddings,
  extracted text, caches, and derived checkpoints, within systems under
  AegisThread operator control (content transmitted to an AI provider
  persists under that provider's retention terms); and each such item shall
  be accessible by default only to the user from whose activity it was
  derived, with any wider sharing requiring an explicit, recorded product
  decision (OQ-17 in the
  [register](../development/implementation-plan.md#open-questions-and-assumptions-register))
  and requests by any other user denied and recorded until such a decision
  exists.
- **FR-ASYNC** — Long-running work (ingestion, embedding, evaluation runs,
  deletion cascades) shall execute asynchronously with retry, failure
  recording, and idempotent re-execution.
- **FR-OHTTP** — The client-to-gateway submission path shall use OHTTP
  encapsulation as scoped in [ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md),
  integrated behind the transport abstraction of
  [ADR-0005](../adr/0005-ohttp-behind-transport-abstraction.md).
- **FR-OPS** — The system shall expose health status and the effective
  runtime configuration to operators.

## Retrieval quality policy and evaluation design

**No retrieval-quality or answer-quality claim may be published anywhere in this repository until evaluation results produced by the procedure below exist and are cited.**

### Metric candidates

- Recall@k
- Precision@k
- MRR (mean reciprocal rank)
- Citation faithfulness (share of citations supported by retrieved evidence)

### Dataset requirements

Evaluation datasets must be held-out (never part of an ingested corpus),
tenant-representative (reflecting the real document types and language of the
target tenant), of sufficient size for the chosen metrics to be
interpretable, and licensed for the intended use. No dataset is named here;
see the register statement below.

### Procedure

1. **Golden-set construction** — build questions paired with the evidence
   that must be retrievable for them.
2. **Run** — execute retrieval and answering against the golden set under
   recorded parameters.
3. **Report** — compute the metrics and publish the results together with
   the run parameters.
4. **Review** — review results before any statement is derived from them.

### Register statement

Concrete datasets and numeric thresholds are deliberately **not** chosen in
this document; they live only in the open-questions register (OQ-06 in the
[register](../development/implementation-plan.md#open-questions-and-assumptions-register))
and must be fixed there before the M4 evaluation harness executes.

## MVP non-goals

- **No microservices.** The MVP is a modular monolith
  ([ADR-0001](../adr/0001-modular-monolith-first.md)); service extraction
  requires a concrete, documented need.
- **No orchestration framework commitment.** The runtime orchestration
  approach is an open decision
  ([ADR-0006](../adr/0006-ai-runtime-orchestration-deferred.md)); the MVP
  uses the working default without committing to a framework.
- **No AI provider selection.** Provider and embedding-model selection is
  out of MVP scope-definition and tracked as OQ-05; all access sits behind
  [ADR-0008](../adr/0008-provider-neutral-ai-boundary.md).
- **No threat model in scope.** The threat model is explicitly deferred and
  tracked as OQ-12; the privacy requirements in the
  [system design](../architecture/system-design.md#privacy-and-data-protection-requirements)
  are binding regardless.
- **No SSO or enterprise federation.** Organizational identity integration is
  post-MVP; the MVP uses minimal local accounts so that the authorization
  architecture can be proven without an IdP dependency.
- **No mobile clients.** The MVP targets browser-based access only; mobile
  clients would multiply the surfaces the privacy architecture must cover
  before the core is proven.
- **No real-time collaboration.** Multi-user simultaneous editing introduces
  state-conflict machinery out of proportion for the MVP; sessions are
  single-user.

## Links

- [Product vision](product-vision.md)
- [Glossary](glossary.md)
- [System design](../architecture/system-design.md)
- [Implementation plan](../development/implementation-plan.md)
- [ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md), [ADR-0005](../adr/0005-ohttp-behind-transport-abstraction.md), [ADR-0006](../adr/0006-ai-runtime-orchestration-deferred.md), [ADR-0007](../adr/0007-mcp-capability-boundary.md), [ADR-0008](../adr/0008-provider-neutral-ai-boundary.md)
