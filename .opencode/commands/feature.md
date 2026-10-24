---
description: Full feature lifecycle — brief, plan, implementation, independent reviews, checks, docs, delivery report
agent: build
---

Coordinate this feature through the AegisThread engineering workflow.

**Feature:** $ARGUMENTS

You are the coordinator. You delegate to the specialized subagents; you do
not implement yourself. The `implementer` agent is the ONLY agent that edits
application code in this workflow. Reviewers are read-only by configuration
and must not be asked to edit.

If the feature description above is too thin to start (no user, no problem,
no desired outcome), ask the user to clarify before proceeding.

## Phase 0 — Ground truth

1. Run `git status` and record the current state, including pre-existing
   uncommitted or untracked work. The repository may contain unrelated
   uncommitted work — it is off-limits. Identify which part of the codebase
   the feature targets.

## Phase 1 — Product brief

2. Delegate to `product-owner` with the feature description and relevant
   repository context. Require its full output format (problem statement,
   user story, requirements, acceptance criteria, edge cases, non-goals,
   risks, assumptions).

## Phase 2 — Technical plan

3. Delegate to `architect` with the complete brief. The architect must
   inspect the actual codebase first and produce the implementation plan in
   its required output format.
4. Verify the plan covers EVERY acceptance criterion and respects the
   non-goals before proceeding. If there is a gap, send it back to the
   architect with the specific gaps. Do not start implementation with an
   uncovered criterion.

## Phase 3 — Implementation

5. Delegate to `implementer` with the approved brief and plan. Instruct it to
   follow its required output format, including real check results. Only this
   agent writes code from here until the fix phase.

## Phase 4 — Independent reviews

6. Delegate reviews of the resulting diff. Run in parallel where practical:
   - `code-reviewer` — always.
   - `security-reviewer` — always for auth, data, input, or AI-driven
     changes; otherwise if any security surface is touched.
   - `qa-reviewer` — always when acceptance criteria exist.
   Give each reviewer the diff scope, the acceptance criteria, and the plan.

## Phase 5 — Checks and fixes

7. With the `qa-reviewer` output in hand, run the available deterministic
   checks of the repository (test suite, static analysis, formatter
   check). Record exact commands and real results. If a check cannot run,
   record why.
8. Delegate to `implementer` to address CONFIRMED findings only. Speculative
   findings get recorded, not fixed reflexively.
9. Re-review only the affected areas (code/security reviewers for the fixes)
   and rerun the checks that the fixes could affect. Iterate until no
   Critical or High findings remain, or until the remaining ones are
   explicitly accepted by the user with a recorded rationale.

## Phase 6 — Documentation and delivery

10. Delegate to `docs-reviewer` to verify documentation consistency of the
    final state. Have the `implementer` apply confirmed documentation fixes.
11. Produce the delivery report (below). Distinguish clearly between
    implemented, tested (with evidence), and unverified behavior. Never
    present generated code alone as "complete".

## Delivery report format

- **Feature** — one-line summary and affected area(s) of the codebase.
- **Acceptance criteria status** — each: implemented + evidence (file, test).
- **Files changed.**
- **Checks run** — commands and real summarized results, or why not run.
- **Review findings** — resolved / accepted-with-rationale / open.
- **Documentation updates.**
- **Unresolved findings, risks, and assumptions.**
- **Suggested commit sequence** — from `/commit-plan` conventions.

## Hard rules

- Do NOT create commits, push, merge, or deploy. The human decides after
  reading the delivery report.
- Reviewers never edit files. Only `implementer` edits during this workflow.
- Never claim a task or test passed without real execution evidence from this
  session.
- Preserve pre-existing unrelated changes.
