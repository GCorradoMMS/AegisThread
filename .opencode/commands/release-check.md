---
description: Release readiness assessment — PASS, PASS WITH RISKS, or FAIL, with evidence
agent: build
---

Assess whether the current change is ready for human approval. Produce a
verdict backed by evidence, not optimism.

Working-tree status:

```
!`git status --short`
```

**Scope (optional):** $ARGUMENTS

## Checks

Work through each item. For every item, state: evidence found, evidence
missing, or not applicable (with reason). Do not fabricate results.

1. **Working-tree and diff status** — what is actually changed; any
   unexpected or unrelated files swept in; leftover debug artifacts,
   secrets, or large binaries.
2. **Acceptance-criteria coverage** — delegate to `qa-reviewer` if no
   current coverage matrix exists; otherwise verify each criterion maps to a
   test or explicit manual verification.
3. **Test execution evidence** — which suites ran, in this session or a
   referenced session, with what results. "Should pass" is not evidence.
4. **Static analysis and formatting** — run the configured linters/analyzers
   of the repository if any exist; report real output.
5. **Database migration implications** — new migrations? Reversible? Safe
   deploy order with the code that uses them? Data-loss risk?
6. **Security and review findings** — Critical/High findings from
   `/review` or this session must be resolved or explicitly accepted by the
   user with a recorded rationale.
7. **API compatibility** — breaking changes to routes, payloads, events, or
   contracts consumed elsewhere; versioning or deprecation notes needed.
8. **Documentation updates** — README, env examples, ADRs, runbooks updated
   or explicitly justified as unchanged (delegate to `docs-reviewer` if
   unclear).
9. **Build and CI status** — report ONLY what is actually accessible (local
   build, CI output the user pastes or a command you actually ran). If CI is
   not accessible, write "CI status unknown — not verified", never
   "CI passing".
10. **Remaining limitations** — everything that could not be checked and
    why.

## Verdict

- **PASS** — all applicable checks have positive evidence, no open
  Critical/High findings.
- **PASS WITH RISKS** — shippable, but named risks remain; list them with
  owners/next steps.
- **FAIL** — blocking findings, missing evidence on critical checks, or
  failing tests. List exactly what blocks approval.

Include a short table: check → status (✓ / ✗ / ? / n-a) → evidence.

## Hard rules

- A PASS is NOT permission to deploy. The human decides. Say so in the
  report.
- Never fabricate CI results, test results, or review outcomes.
- Do not fix anything in this command; report only.
- Do not create commits, push, merge, or deploy.
