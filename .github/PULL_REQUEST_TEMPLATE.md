<!--
PR checklist guidance: every section below must reflect reality.
Never fill in test results that were not actually observed.
-->

## Problem and motivation

<!-- What user or business problem does this solve, and why now? -->

## Related issue

<!-- Issue reference, e.g. Closes #123, or "none" with reason -->

## Main changes

<!-- Concise list of the substantive changes, grouped by responsibility -->

## Architecture decisions and trade-offs

<!-- Key design decisions, alternatives rejected, ADRs added (docs/adr/NNNN-*.md) -->

## Tests and exact commands run

<!-- List the test suites and checks that RAN, with the exact commands and results.
     If something could not run, say so here — do not leave it implied. -->

- [ ] Tests executed — commands and results:
- [ ] Static analysis / formatting executed — commands and results:
- [ ] Manual verification performed (describe):

## Database / API compatibility

<!-- Migrations (reversible? deploy order?), API/contract changes, backward compatibility -->

- [ ] No migration, or migration reviewed for reversibility and deploy order
- [ ] No API/contract change, or change is backward compatible / versioned and documented

## Security and privacy implications

<!-- Auth, authorization, data isolation, logging of sensitive data, secrets,
     input handling, AI/tool execution surfaces. Link security review notes if run. -->

## Documentation updates

<!-- README, docs/, ADRs, env examples, runbooks — updated or justified as unchanged -->

## Remaining risks and unverified areas

<!-- Anything not covered by automated checks: untested integrations, pending CI, assumptions -->

## Reviewer checklist

<!-- For the human and reviewer agents -->

- [ ] Acceptance criteria are covered and mapped to tests
- [ ] No open Critical/High review findings (or accepted with rationale)
- [ ] Architectural boundaries respected (see AGENTS.md)
- [ ] Security review performed where the change touches auth, data, input, or AI surfaces
- [ ] Documentation tells the same story as the code
- [ ] Commit history follows docs/engineering/commit-conventions.md
