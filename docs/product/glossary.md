<!-- aegis-thread doc header -->
# Glossary

Status: Specification (draft for human review)
Date: 2026-10-24

Definitions live here and only here; other documents link terms instead of
redefining them.

### AegisThread

The product defined by this documentation: a Symfony/PHP application for
privacy-conscious, retrieval-based AI assistance over an organization's own
documents. The project is in the specification phase; this corpus is the
specification, not a description of working software.

### Tenant

The isolation boundary for users, workspaces, and their data. All data shall
be isolated per tenant at every layer; no cross-tenant read path shall exist
at any layer — by design, and to be verified once implementation exists
([system design: privacy](../architecture/system-design.md#privacy-and-data-protection-requirements)).

### Modular monolith

One deployable application with explicit in-process module boundaries treated
as architectural contracts ([ADR-0001](../adr/0001-modular-monolith-first.md)).

### Source of truth

The single authoritative store for durable product state — PostgreSQL for
AegisThread ([ADR-0002](../adr/0002-postgresql-source-of-truth.md)). Derived
artifacts are never authoritative.

### Conversation

An interactive exchange between a user and the assistant within one session
context.

### Short-term conversation context

Request-scoped information assembled to serve the current exchange;
ephemeral and never persisted. Full construct definition:
[system design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs).

### Episodic memory

Candidate facts derived from a session that are not yet approved for
long-term retention; unapproved data. Full construct definition: see
[system design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs).

### Long-term approved memory

Facts a user has explicitly approved for persistent retention. Full construct
definition: see
[system design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs).

### Workflow checkpoint

Resumable execution state of a long-running task, used to continue work after
interruption; not conversation data. Full construct definition: see
[system design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs).

### Conversation history

The verbatim transcript record of exchanges in a conversation; a record,
distinct from every memory construct. Full construct definition: see
[system design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs).

### Memory lifecycle

The governed life of persistent memory: derivation as episodic candidates,
explicit user approval into long-term memory, and the user rights to inspect,
correct, and delete ([MVP scope: FR-MEM](mvp-scope.md#functional-requirements)).

### RAG

Retrieval-augmented generation: answering questions by retrieving candidate
evidence from ingested documents and assembling an answer grounded in that
evidence.

### Retrieval evaluation

The measurement discipline for retrieval behavior — metrics, dataset
requirements, and procedure — defined in
[MVP scope: Retrieval quality policy and evaluation design](mvp-scope.md#retrieval-quality-policy-and-evaluation-design).
No retrieval-quality claim exists without it.

### Citation

A reference in an answer that points to a specific piece of retrieved
evidence.

### Citation integrity

The requirement that every citation resolve to evidence actually retrieved
for that answer ([MVP scope: FR-CIT](mvp-scope.md#functional-requirements)).

### Derived data

Artifacts computed from source data — extracted text, embeddings, caches,
derived checkpoints. Always rebuildable, never authoritative, and deleted
together with their source ([ADR-0002](../adr/0002-postgresql-source-of-truth.md)).

### Retention

How long each construct is kept. Retention is per construct; concrete
periods are open (OQ-11 in the
[register](../development/implementation-plan.md#open-questions-and-assumptions-register)).

### Prompt injection

Attempts to smuggle instructions to the model through untrusted content such
as documents or tool output. Such content is data, never instructions
([system design: privacy](../architecture/system-design.md#privacy-and-data-protection-requirements)).

### Outbound request restriction

The default-deny policy governing requests AegisThread sends to external
services; egress is allowed only to explicitly permitted targets.

### OHTTP

Oblivious HTTP (RFC 9458): a client encrypts an HTTP request for a gateway
and sends it through a relay; the relay cannot read the contents, and the
gateway decapsulates by design. It protects the client-to-gateway boundary
only ([ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md)).

### Relay (OHTTP)

The forwarding party in OHTTP. It sees the client's network identity and
gateway URI metadata; it never sees the protected request contents.

### Gateway (OHTTP)

The terminating party in OHTTP. It decapsulates requests — seeing their
content by design — enforces the target policy, and forwards to the target.

### Encapsulated request

An OHTTP-protected request: encrypted for the gateway and opaque to the
relay.

### MCP

Model Context Protocol: the protocol AegisThread uses to expose a scoped
tool capability surface to the agent runtime
([ADR-0007](../adr/0007-mcp-capability-boundary.md)). It never substitutes
for authentication, authorization, or business logic.

### Bounded agent loop

The conversation runtime's model → proposed action → authorization → tool
execution → observation cycle with a hard, application-enforced iteration
limit. The working default per
[ADR-0006](../adr/0006-ai-runtime-orchestration-deferred.md).

### Provider-neutral boundary

The application-owned seam through which all model and embedding access
happens, keeping provider selection reversible
([ADR-0008](../adr/0008-provider-neutral-ai-boundary.md)).

## Links

- [Product vision](product-vision.md)
- [MVP scope](mvp-scope.md)
- [System design](../architecture/system-design.md)
- [Implementation plan](../development/implementation-plan.md)
- [ADR directory](../adr/0001-modular-monolith-first.md)
