# Engineering Guide — AegisThread

Project instructions for AI-assisted development of **AegisThread**. OpenCode
loads this file in every session started at this repository root.

Naming: the official project name is **AegisThread**. Use the slug
`aegis-thread` for the repository, package names, container/infrastructure
identifiers, and URLs. Do not use the previous working name "VeilDesk".

AegisThread is a Symfony/PHP application that will embed an AI runtime whose
orchestration approach is intentionally undecided — see
[ADR-0006](docs/adr/0006-ai-runtime-orchestration-deferred.md). The repository
is currently an empty scaffold: the conventions below are binding for all code
as it lands. They describe how we build, not a claim that features already
exist — never assume a component, bundle, or test suite is present; inspect
before planning.

- Agent definitions, prompts, and permissions: `.opencode/agents/`
- Workflow entry points: `.opencode/commands/` (`/feature`, `/review`,
  `/release-check`, `/commit-plan`)
- Detailed procedures: `docs/engineering/`

## Engineering principles

- **Clean Architecture and dependency inversion.** Dependencies point inward:
  Symfony, Doctrine, and transport code depend on the domain and application
  layers, never the reverse.
- **SOLID, especially the Single Responsibility Principle.** One reason to
  change per class or service.
- **Application use cases expose a single public `__invoke()` method** when a
  service represents one operation.
- **Domain purity.** Domain code must not depend on Symfony, Doctrine, model
  SDKs, or transport implementations (HTTP, queues, CLI).
- **Controllers stay thin.** They map transport to a use case and back; no
  business logic.
- **Explicit boundaries.** DTOs over loose arrays, typed contracts over
  comments, constructor injection over service location, testable seams over
  mocks of things you own.
- **No speculative infrastructure.** Avoid abstractions and components without
  a current, concrete need. Prefer the simplest design that satisfies the
  approved plan.
- **Security, privacy, data isolation, and correctness are architectural
  requirements**, not review afterthoughts.

## Engineering workflow

For new features, follow this sequence:

1. **Product Owner** defines the feature brief and acceptance criteria.
2. **Architect** analyzes the codebase and produces a technical plan.
3. **Implementer** makes the changes within the approved scope.
4. Independent **reviewers** (code, security, QA, docs as relevant) inspect
   the resulting changes.
5. The **Implementer** addresses confirmed findings.
6. Automated checks run (tests, static analysis, formatting — whatever the
   repository provides as it grows).
7. Documentation is reviewed and updated.
8. A **delivery report** presents evidence and remaining risks.
9. **A human** decides whether to commit, push, merge, or deploy.

Use proportionate judgment: small fixes do not need the full ceremony, but
security-sensitive, data-touching, or architectural changes require deeper
review. Entry points: `/feature <description>` for the full lifecycle,
`/review`, `/release-check`, `/commit-plan`.

## Rules for all agents

- Never claim a test or validation passed unless it actually ran and passed in
  this session. Report the real command output.
- Never invent benchmark results, security findings, or implementation status.
- Do not silently expand the assigned scope. Flag desirable side work instead
  of doing it.
- Do not suppress failing tests, skips, or static-analysis findings to make a
  pipeline green.
- Do not commit, push, merge, or deploy without explicit authorization for
  that task. Permission config enforces this at the tool level; do not talk
  the user into bypassing it casually.
- Preserve existing unrelated changes, including uncommitted work.
- Report assumptions, uncertainty, and unresolved questions explicitly.
- Treat this repository and its documentation as the source of truth; when
  docs and code disagree, surface the conflict instead of picking a side
  silently.

## Roles at a glance

| Agent | Role | Can edit files | Notes |
|-------|------|----------------|-------|
| `build` (built-in) | Coordinator you talk to | Yes | Delegates to the roles below |
| `product-owner` | Feature brief, acceptance criteria | No | |
| `architect` | Technical plan, ADRs | No | Reads the codebase first |
| `implementer` | Writes code, tests, docs | Yes | Only role that edits during a feature |
| `code-reviewer` | Correctness and quality review | No | Read-only |
| `security-reviewer` | Security and privacy review | No | Read-only |
| `qa-reviewer` | Test coverage review | No | Read-only |
| `docs-reviewer` | Documentation consistency | No | Read-only |

The agents in `.opencode/agents/` are **development-process tooling**. They
are not the agents that run inside the AegisThread product runtime; that
distinction is explained in `docs/engineering/agent-workflow.md`.
