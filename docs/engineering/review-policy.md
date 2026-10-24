# Review Policy

How findings from the reviewer agents are graded, verified, and resolved.

## Severity definitions

| Severity | Definition | Blocks human approval? |
|----------|-----------|------------------------|
| **Critical** | Exploitable security hole, data loss/corruption, cross-user/cross-tenant data exposure, or a defect that breaks the feature's core promise | Yes |
| **High** | Likely regression, race condition with realistic trigger, missing authorization check, broken migration path, missing test for a critical path | Yes (fix or explicit written acceptance) |
| **Medium** | Edge case with bounded impact, misleading error handling, maintainability hazard, test that verifies the wrong thing | No — must be listed in the delivery report |
| **Low** | Style, naming, minor duplication, documentation wording | No |

## Standards of evidence

A finding is only as good as its evidence. Every finding must state:

1. **Location** — file and line, verified to exist in the current tree.
2. **Evidence** — what the code actually does (quote or precise description).
3. **Failure scenario** — the concrete sequence that triggers it.
4. **Impact** — who or what is harmed.
5. **Fix** — the recommended remediation.
6. **Regression test** — the test that would have caught it.

Findings that cannot yet meet this bar are **not discarded**; they are
labeled *Possible issue — needs verification* with the exact evidence that
would settle them. Security reviewers additionally separate **confirmed
findings** from **questions/risks** (things requiring runtime, configuration,
or infrastructure evidence).

## Verification duty

- Reported file/line references are verified against the working tree before
  findings are aggregated (`/review` does this explicitly).
- "Tests pass" claims require real execution evidence from the session.
  Reading test code is not execution.
- A source-code security review is not a penetration test and not a
  dependency, infrastructure, or runtime audit. No report may claim overall
  security assurance.
- Documentation claims (feature status, benchmark results) must be backed by
  evidence or flagged as inaccurate — even when the claim is favorable.

## Which findings block approval

- All **Critical** findings block.
- **High** findings block unless the human explicitly accepts the risk with a
  recorded rationale (recorded in the delivery report / PR description).
- **Medium** and **Low** findings never block but must appear in the delivery
  report so acceptance is informed.

## Resolving reviewer disagreement

Disagreements between reviewers (or between a reviewer and the Implementer)
are resolved **by inspecting evidence, not by majority vote**:

1. Restate each position as a falsifiable claim ("X fails when Y happens").
2. Identify the evidence that discriminates between the claims (a test, a
   trace, the actual diff, the actual behavior).
3. Produce or locate that evidence. If it cannot be produced in-session, the
   item is recorded as *unverified* and treated as blocking when the risk
   would be High or worse.
4. The coordinator records the resolution and its evidence. Severity is never
   averaged and votes are never counted.

## Reviewer independence

- Reviewers are read-only by configuration; they never edit files.
- The Implementer addresses *confirmed* findings and answers others with
  evidence; it may dispute a finding, but disputes go through the
  disagreement procedure above.
- The coordinator aggregates but does not downgrade severities; if it
  disagrees with a severity it appends its reasoning for the human, and the
  human decides.
