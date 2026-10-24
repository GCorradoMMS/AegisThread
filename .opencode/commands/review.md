---
description: Read-only multi-reviewer review of the current changes — no files are modified
agent: build
---

Review the current changes WITHOUT modifying anything.

**Scope (optional):** $ARGUMENTS

You are the coordinator. You delegate to read-only reviewer subagents,
aggregate their findings, and report. You do not fix anything in this
command.

Current working-tree status:

```
!`git status --short`
```

## Determine the change set

1. If `$ARGUMENTS` names files, directories, or a feature description, treat
   that as the review scope.
2. Otherwise use the working-tree diff (`git diff` for unstaged,
   `git diff --cached` for staged) plus untracked files relevant to the
   current task.
3. IMPORTANT: this repository may have no commits yet, in which case
   `git diff` is empty and everything is untracked. In that case, ask the
   user which files or directories constitute the change under review (or
   use the task context from this session).
4. If a base branch or commit is identifiable (e.g. `main` with history),
   also consider `git diff <base>...` for branch-level review.

## Independent reviews

Delegate, in parallel where practical, giving each reviewer the exact change
set and any task context:

- `code-reviewer` — correctness, regressions, concurrency, error handling,
  architecture boundaries, test quality. Run for every review.
- `security-reviewer` — run when the change touches auth, user data, input
  handling, file uploads, URLs, queues, AI/tool execution, or secrets. When
  unsure, run it anyway; say why.
- `qa-reviewer` — run when acceptance criteria exist or tests changed.
- `docs-reviewer` — run when docs, README, env examples, API contracts, or
  operational docs are affected.

Reviewers are read-only by configuration. They must not edit files, and they
must distinguish confirmed findings from speculative ones.

## Aggregate and verify

1. Collect all findings and remove duplicates (same root cause reported by
   multiple reviewers = one finding, all evidence merged).
2. Verify every reported file and line number actually exists in the current
   working tree; drop or correct phantom references and note the correction.
3. Keep each finding's severity, but annotate disagreements between
   reviewers as "disputed — resolve by evidence" rather than averaging
   severities.

## Report format

- **Executive summary** — what was reviewed, overall assessment in 3-5
  sentences, counts by severity.
- **Findings by severity** — Critical, High, Medium, Low. Each with:
  location, evidence, concrete failure scenario, impact, proposed
  remediation, source reviewer.
- **Missing tests** — from the QA review.
- **Documentation gaps** — from the docs review.
- **Disputed findings** — the conflicting claims and what evidence would
  resolve them.
- **Areas not verified** — explicit list; never paper over this.

## Hard rules

- Do not edit, create, or delete files. Do not stage, commit, or stash.
- Do not run the test suite unless the user explicitly asks; this command is
  about static review. Report execution status as reported evidence only.
- Do not claim anything is secure, correct, or complete — report evidence
  and gaps.
