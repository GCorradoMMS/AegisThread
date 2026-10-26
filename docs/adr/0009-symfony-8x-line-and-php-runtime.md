# ADR-0009: Symfony 8.x line and PHP 8.5 runtime

Status: Accepted
Date: 2026-10-25
Deciders: Human (constraint fixed 2026-10-25) / Architect (formalization)

## Context

The M1 skeleton cannot bootstrap until the framework version line is fixed
([OQ-02](../development/implementation-plan.md#open-questions-and-assumptions-register)):
the choice sets the PHP floor, the dependency lines that must be compatible
(Doctrine, PHPUnit), and the support window the project lives with. The
fact-set verified on 2026-10-24 left two viable options — the Symfony 7.4 LTS
line and the 8.x line — and recorded the question as open. The human owner
decided on 2026-10-25; this record formalizes that decision.

Verified fact-set (2026-10-25,
[https://symfony.com/releases](https://symfony.com/releases) and
[https://endoflife.date/symfony](https://endoflife.date/symfony)):

- Symfony **8.1** is the current stable release, stable since 2026-05-29;
  requires **PHP ≥ 8.4**; latest patch release 8.1.9; **all support ends
  2027-01-31**.
- Symfony 8.0 reached end of life on 2026-07-31.
- Symfony 8.2 is due 2026-11; the next 8.x LTS is 8.4, expected 2027-11.

Verified PHP support facts (2026-10-25,
[https://www.php.net/supported-versions.php](https://www.php.net/supported-versions.php)):
PHP 8.4 exits active (bug-fix) support in November 2026 and continues on
security fixes only; PHP 8.5 is in active support.

## Decision

AegisThread builds on the **Symfony 8.x line, starting at 8.1**, with
**PHP 8.5 as the Docker runtime of record** (image `php:8.5-cli`). This
closes OQ-02.

Stated honestly: 8.1's support window ends 2027-01-31. The project therefore
commits to tracking the 8.x release cadence — 8.2 next, then onward — until
the 8.4 LTS, and framework upgrades are part of routine maintenance until
that LTS is reached and adopted. Falling behind the 8.x cadence is not an
accepted steady state; it is debt to be paid on a planned schedule.

## Rationale

- **Why the 8.x line:** it avoids onboarding the project onto a major line
  whose successor is already shipping, and it keeps the framework on the
  current-stable feature cadence the project will live with until the 8.4
  LTS. This was the human owner's explicit choice over the 7.4 LTS
  alternative below.
- **Why PHP 8.5 as runtime of record rather than the 8.4 floor:** 8.4 exits
  active (bug-fix) support in November 2026, immediately after this decision;
  the Doctrine stack's CI matrix already includes PHP 8.5, so the persistence
  layer is exercised on it upstream; and the development host toolchain runs
  PHP 8.5. Because all local and CI execution runs in Docker, the image —
  not the host — is the contract; the host merely needs to run Docker.

## Alternatives considered

- **Symfony 7.4 LTS.** Security support until 2029-11-30 with a PHP floor of
  8.2. Rejected: it forks the stack from the current stable line the project
  would have to cross eventually, trading a known upgrade deadline for a
  larger one. Recorded as the serious alternative.
- **PHP 8.4 as runtime of record.** Rejected: it puts the runtime on an
  immediate security-only trajectory (active support ends 2026-11) while the
  host toolchain and the Doctrine CI matrix are already on 8.5.
- **Waiting for the 8.4 LTS (expected 2027-11).** Rejected: it blocks M1 and
  every milestone behind it for over a year with no compensating benefit.

## Consequences

- Minimum PHP 8.4 anywhere Symfony code runs; the runtime of record for
  development and CI is PHP 8.5 in Docker, making local/CI execution
  Docker-only by policy.
- Dependency lines that track Symfony 8.x (DoctrineBundle, PHPUnit) resolve
  to the newest constraint-compatible versions. Documented fallback rule:
  when a newly resolved release breaks the build, pin the last known-good
  version, record the pin with its reason, and lift the pin once upstream
  fixes the regression.
- A fast-follow upgrade to Symfony 8.2 (due 2026-11) is anticipated and is
  routine maintenance, not a project; the same holds for subsequent 8.x
  releases until 8.4 LTS.

## Links

- [ADR-0001](0001-modular-monolith-first.md) — the modular monolith this framework choice serves
- [Implementation plan: Open questions and assumptions register](../development/implementation-plan.md#open-questions-and-assumptions-register) — OQ-02 closure note
- [System design: Technology stack](../architecture/system-design.md#technology-stack)
