# Commit Conventions

The AegisThread repository uses **Conventional Commits**.

## Commit message format

```
<type>(<scope>): <subject>

[optional body]

[optional footer(s)]
```

- **type** — one of: `feat`, `fix`, `docs`, `test`, `refactor`, `perf`,
  `chore`, `ci`, `build`, `revert`.
- **scope** — the component, e.g. `api`, `runtime`, `domain`, `web`,
  `infra`, `tooling`. The repository is a young scaffold: fix the canonical
  scope list as top-level components emerge, and keep it aligned with the
  directory structure.
- **subject** — imperative, present tense, no trailing period, ≤ 72 chars.
- **body** (optional) — *what* and *why*, not a line-by-line diff narration.
- **footers** — `Refs: #123`, `BREAKING CHANGE: <explanation>` for API or
  contract breaks.

Examples:

```
feat(api): add workspace invitation use case

Adds the application-layer use case with __invoke() entry point and a
Doctrine repository adapter. Domain stays framework-free.

Refs: #42
```

```
fix(wiphaven): prevent duplicate webhook processing on retry
```

```
chore(tooling): add qa-reviewer agent permissions
```

## Branch naming

```
<type>/<issue>-<short-slug>      e.g. feat/42-workspace-invites
                                 e.g. fix/57-webhook-retry-duplicates
chore/tooling-<topic>            e.g. chore/tooling-review-policy
```

Branches are created per feature or fix; long-lived personal branches and
unbounded scope ("grab bag") branches are avoided.

## Logical commit boundaries

- One responsibility per commit: application behavior, tests,
  infrastructure/build config, migrations, documentation, and dev tooling are
  separate commits (tests travel with the behavior they verify unless the
  suite is being reorganized).
- Every commit should be individually comprehensible — ideally buildable —
  on top of the previous one. Migrations commit before the code that depends
  on them.
- Unrelated pre-existing changes in the working tree are never swept into a
  feature commit. `/commit-plan` lists them as excluded user work.

## Push, merge, and deploy rules

- **Default: no automatic push or merge.** Agents propose commit sequences;
  the human decides what to commit and when to push or merge.
- Force pushes, history rewrites (`reset --hard`, `clean`), and deploys are
  denied to agents at the permission level, not just discouraged in prose.
- If you genuinely need an agent to perform a push for an approved task,
  approve the specific prompted command — do not widen the standing
  permissions for convenience.

## Tooling changes

Changes to `.opencode/agents/`, `.opencode/commands/`, `opencode.json`, this
directory, and `AGENTS.md` are themselves reviewed changes
(`type: chore`, scope `tooling`) — the workflow that reviews code should be
able to trust its own configuration history.
