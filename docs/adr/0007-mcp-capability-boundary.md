# ADR-0007: MCP as a scoped capability boundary; authorization independent of model decisions

Status: Accepted
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

The agent runtime executes tools on behalf of a conversation. Model output is
untrusted input: it must never be the authority that grants capability. Tool
access also tends to grow into an implicit, unreviewed permission surface —
exactly the surface prompt-injection attacks try to reach through untrusted
documents and tool output.

## Decision

**Model Context Protocol (MCP) is adopted as the single, scoped capability
surface** between the agent runtime and tools. Every tool invocation passes
an application-side authorization decision that is **independent of model
output**. MCP never substitutes authentication, authorization, or business
logic; it exposes scoped capabilities only. Capability scoping, authorization
rules, and audit records are application responsibilities.

## Rationale

- Keeping the authorization decision in application code — decided by the
  application, never by the model — puts the trust boundary where it is
  testable and auditable.
- A single scoped surface makes the permission review surface explicit and
  bounded, which contains the prompt-injection surface described in the
  [system design: privacy section](../architecture/system-design.md#privacy-and-data-protection-requirements).
- Tool results are untrusted data; routing them through one surface makes
  their treatment uniform.

## Alternatives considered

- **Direct in-process function calling per model.** Rejected: implicit,
  scattered permissions with no single surface to review or audit.
- **Trusting model-declared tool intent.** Rejected: model output is
  untrusted input; making it an authority violates the decision.
- **No tools in the MVP.** Rejected: the conversation capability requires a
  tool surface; the risk is managed by scoping, not avoidance.

## Consequences

- Tool onboarding requires a capability scope, an authorization rule, and an
  audit record — no tool is callable without all three.
- The MCP client/SDK choice is *Proposed* with a recorded Tier-3 maturity
  risk (OQ-04 in the
  [register](../development/implementation-plan.md#open-questions-and-assumptions-register);
  [technology stack](../architecture/system-design.md#technology-stack)).
- The bounded agent loop treats tool results as data, never as instructions
  (see [ADR-0006](0006-ai-runtime-orchestration-deferred.md) for the loop
  decision).

## Links

- [ADR-0006](0006-ai-runtime-orchestration-deferred.md) — orchestration decision inside which MCP sits
- [ADR-0008](0008-provider-neutral-ai-boundary.md) — model access boundary
- [System design: Conversation and bounded agent loop; MCP interaction](../architecture/system-design.md#conversation-and-bounded-agent-loop-mcp-interaction)
- [MVP scope: FR-MCP](../product/mvp-scope.md#functional-requirements)
- [Glossary: MCP](../product/glossary.md#mcp)
