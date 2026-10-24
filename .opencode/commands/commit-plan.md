---
description: Propose a sequence of logical Conventional Commits for the current change — creates nothing
agent: build
---

Propose a commit sequence for the current change. Do NOT create any commits.

Working-tree status:

```
!`git status --short`
```

**Scope (optional):** $ARGUMENTS

## Steps

1. Inspect the actual change: `git status`, `git diff`, `git diff --cached`,
   and untracked files. In a repository with no baseline commit (this one,
   until the initial commit exists), everything is untracked — group by path
   and content instead of diff hunks.
2. Read enough of each changed file to understand its responsibility. Do not
   group by file path alone.
3. Partition the changes into coherent commits, each with a single
   responsibility, ordered so that every commit is logically buildable on
   top of the previous one. Typical grouping, split or merged as the change
   dictates:
   - application behavior (one logical feature step per commit),
   - tests accompanying that behavior,
   - infrastructure / build / CI configuration,
   - database migrations,
   - documentation,
   - development tooling (agents, commands, workflow docs).
4. Never mix unrelated pre-existing user changes into feature commits. If
   the working tree contains changes that clearly do not belong to this
   feature, list them separately under "Excluded — user work, do not commit
   with this feature" and explain, rather than rewriting or discarding
   anything.

## Output format

For each proposed commit, in order:

- **N. `<type>(<scope>): <subject>`** — Conventional Commit message
  (types: feat, fix, docs, test, refactor, perf, chore, ci, build; scope =
  component, e.g. `feat(api): ...`; canonical scopes live in
  `docs/engineering/commit-conventions.md`).
  - Files / logical change group (exact paths or globs).
  - Rationale — why this is one coherent commit.
  - Depends on — which earlier commits it builds on (or "none").
  - Relevant tests / checks — what proves this commit is sound.

Finish with:

- **Excluded changes** — unrelated user work that must not be committed as
  part of this feature, if any.
- **Warnings** — e.g. commits that cannot be made independently buildable,
  secrets or artifacts that must not be committed at all, files that need a
  `.gitignore` decision first.

## Hard rules

- Never run `git add`, `git commit`, `git stash`, or any history-changing
  command. Proposal only.
- Prefer small, comprehensible commits; a reader should understand a commit
  message without opening the diff.
- Obey repository conventions in `docs/engineering/commit-conventions.md`.
- If a clean sequence is impossible without mixing unrelated changes, say so
  explicitly and propose the least-bad alternative for the human to decide.
