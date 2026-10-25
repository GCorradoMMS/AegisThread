# ADR-0006: AI runtime orchestration approach: decision deferred

Status: Open
Date: 2026-10-24
Deciders: Human (constraint fixed 2026-10-24) / Architect (formalization)

## Context

Earlier repository texts described the AegisThread AI runtime as
LangGraph-orchestrated. On 2026-10-24 the human project owner fixed the
opposite constraint for the MVP: no LangGraph and no separate Python
orchestration service in the MVP by default. The general question of how
runtime AI behavior is orchestrated remains genuinely open. This ADR records
the deferral, the working default, the options under consideration, and the
criteria for closing the decision.

## Decision

The orchestration approach for the AI runtime is **intentionally undecided**
and tracked by this Open ADR. For the MVP, a **bounded in-process agent
loop** is the working default (option (a) below). No separate orchestration
service is introduced in the MVP by default. Closing this decision requires
an update to this ADR that satisfies "Decision criteria for closure" below.

## Rationale

- The MVP conversation runtime needs bounded, auditable, small-scope tool
  loops; a full orchestration framework is infrastructure without a
  demonstrated concrete need (no-infrastructure-without-need,
  [ADR-0001, Rationale](0001-modular-monolith-first.md#rationale)).
- Introducing a second (Python) runtime would add a deployment, a data
  boundary, and a supply chain before the product has proven its core loop.
- Keeping the decision open is explicit and reviewed; the prior texts that
  asserted an orchestration approach are reconciled (see Conflicting prior
  statements) rather than silently rewritten.

## Options under consideration

- **Option (a) — bounded in-process agent loop now.** Working default for the
  MVP: the application enforces a hard iteration limit and routes all tool
  use through the scoped capability surface
  ([system design: Conversation and bounded agent loop](../architecture/system-design.md#conversation-and-bounded-agent-loop-mcp-interaction)).
- **Option (b) — LangGraph-orchestrated runtime via a separate Python
  service.** Richest ecosystem for graph-shaped orchestration, at the cost of
  a second runtime, cross-language contracts, and an independent data
  boundary that must satisfy the same isolation and deletion requirements.
- **Option (c) — PHP-native graph workflow engine.** Keeps one language and
  one runtime; requires evaluating or building a workflow engine, which is
  speculative infrastructure until observed loop complexity demands it.

## Decision criteria for closure

This ADR moves to Accepted (for one of the options) when a reviewed decision
record can show:

1. observed loop complexity in M3/M4 that a bounded in-process loop cannot
   express without losing auditability or boundedness; or
2. a requirement for durable, resumable multi-step workflows that workflow
   checkpoints cannot cover in-process; or
3. a concrete, evidenced need for orchestration features that options (b)
   or (c) provide and option (a) cannot;

**and** an evaluation of at least two options against isolation,
observability, and supply-chain impact. Absent these, option (a) remains the
default and this ADR stays Open.

## Conflicting prior statements

As of commit `595b1d5`, two pre-existing texts asserted a specific
orchestration approach as decided:

1. [AGENTS.md](../../AGENTS.md), line 10:
   > AegisThread is a Symfony/PHP application that will embed a LangGraph-orchestrated AI runtime.
2. [agent-workflow.md](../engineering/agent-workflow.md), lines 111–112:
   > …implemented in this repository as part of the Symfony runtime and orchestrated with LangGraph…

Both statements conflicted with the human-fixed constraint recorded under
Decision above. They were reconciled in this increment by a minimal
`chore(tooling)` edit that replaces the orchestration claims with pointers to
this ADR. The separate, negative mention at
[agent-workflow.md](../engineering/agent-workflow.md) line 109 ("**not** a
LangGraph workflow") refers to the development-process tooling and is
unaffected by this ADR.

## Alternatives considered

The alternatives are the three options under "Options under consideration";
they are held open deliberately instead of being rejected. Closing this ADR
without adopting any of them (or while adopting (a) provisionally) is not a
closure.

## Consequences

- MVP planning proceeds on option (a); milestones in the
  [implementation plan](../development/implementation-plan.md#milestones) use
  orchestration-neutral wording.
- No document outside this ADR may state an orchestration approach as chosen.
- Closing the decision changes the architecture documents through a reviewed
  pull request, not by editing prose ad hoc.

## Links

- [ADR-0007](0007-mcp-capability-boundary.md) — the tool execution boundary inside the loop
- [ADR-0008](0008-provider-neutral-ai-boundary.md) — the model access boundary
- [System design: Conversation and bounded agent loop](../architecture/system-design.md#conversation-and-bounded-agent-loop-mcp-interaction)
- [Implementation plan: Milestones](../development/implementation-plan.md#milestones)
