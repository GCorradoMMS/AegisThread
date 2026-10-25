<!-- aegis-thread doc header -->
# Implementation Plan

Status: Specification (draft for human review)
Date: 2026-10-24

## Purpose

This document turns the architecture
([system design](../architecture/system-design.md)) and the product scope
([MVP scope](../product/mvp-scope.md)) into ordered milestones, records every
human-fixed decision with exactly one authoritative location, and keeps the
open questions that gate progress in a single register. It contains no
implementation claims: M0 (this increment) is documentation only.

## Ground rules

- **Status vocabulary (normative everywhere):** *Verified* = external fact
  checked against a primary source on 2026-10-24 (URL required); *Proposed*
  = recommended, not confirmed; *Open* = tracked in the register below as
  OQ-nn.
- Milestones change only via reviewed pull requests.
- Register entries are never deleted; they are closed with a resolution note
  (date and decision reference) once answered.

## Milestones

### M0 — Documentation foundation (this increment)

- **Goal:** the complete specification foundation — product vision, MVP
  scope, system design, the ADR set, glossary, decision register, and README
  — exists and is internally consistent.
- **Testable outcomes:** every deliverable file exists and cross-links
  correctly; the validation suite below runs against the tree and reports the
  expected results.
- **Definition of done:** all acceptance criteria of Increment 0 are met:
  those mapped in the [validation strategy](#validation-strategy) are
  verified by its checks; the process criteria (AC-3 users/problems/value
  structure, AC-6 isolation-required/topology-open, AC-7 no-design-prescriptions
  rule, AC-12 provider neutrality, AC-13 milestone ordering) are verified by
  review, and the Mermaid render check is performed on the GitHub PR preview
  — recorded in the increment's delivery report. The AC list itself is
  defined in the increment brief, which is external to this repository.
- **Depends on:** nothing.

### M1 — Minimal application skeleton, CI, and doc-validation tooling

- **Goal:** the smallest runnable Symfony application that proves the
  toolchain and makes the documentation checks executable in CI.
- **Testable outcomes:** the application boots and exposes a health endpoint
  and a configuration report (first slice of FR-OPS); CI runs the M0
  validation suite plus static analysis and formatting checks on the skeleton;
  the provider-neutral seam ([ADR-0008](../adr/0008-provider-neutral-ai-boundary.md))
  and the OHTTP transport seam
  ([ADR-0005](../adr/0005-ohttp-behind-transport-abstraction.md)) exist as
  application-owned interfaces with no adapter behind them yet; minimal
  local-account scaffolding exists, with no security baseline claims beyond
  what OQ-16's resolution provides.
- **Definition of done:** CI is green on the skeleton; the two seams are
  reviewed; the Symfony version line is fixed (OQ-02) and recorded in an ADR
  or register resolution.
- **Depends on:** M0; resolution of OQ-02; resolution of OQ-16.

### M2 — Ingestion and persistence

- **Goal:** documents flow from submission to stored, retrievable source data
  with rebuildable derived artifacts, asynchronously.
- **Testable outcomes:** the ingestion pipeline executes parse → chunk →
  embed → store asynchronously (C-1, C-6); embeddings are computed through
  the provider-neutral seam behind a test adapter; tenant-scoped storage is
  enforced and tested (no cross-tenant read path at the storage layer);
  object-level access to stored documents is enforced and tested
  (cross-user requests denied); deleting a document deletes its derived
  data.
- **Definition of done:** C-1 and C-6 demonstrable end to end; schema
  migrations reversible; deletion-with-derived-data covered by tests.
- **Depends on:** M1; enough resolution of OQ-01 (topology) and OQ-07
  (queue transport) to design storage and async; embedding-dimension input
  from OQ-05 (before schema freeze).

### M3 — Conversation runtime

- **Goal:** conversational sessions with the bounded agent loop over the
  persisted state constructs, provider-neutral.
- **Testable outcomes:** the bounded loop enforces its iteration limit
  observably (C-3); tool use runs only through the scoped MCP surface with
  application-side authorization (C-4); the five state constructs are
  persisted separately per
  [ADR-0003](../adr/0003-separated-ai-state-persistence.md) with the approval
  gate working; memory lifecycle operations — inspect, correct, delete — work
  for approved memory (C-5).
- **Definition of done:** C-3, C-4, and C-5 demonstrable; no model provider
  dependency outside the seam; real model calls still gated on OQ-05 (test
  adapters unblock development).
- **Depends on:** M2; test adapters behind OQ-05; UI scope decision OQ-14;
  OQ-17 (user-only visibility governs until decided).

### M4 — RAG and evaluation harness

- **Goal:** retrieval-augmented answering with citation integrity, plus the
  harness that executes the evaluation procedure already defined in the
  [MVP scope](../product/mvp-scope.md#retrieval-quality-policy-and-evaluation-design).
- **Testable outcomes:** answers carry citations that resolve to retrieved
  evidence, measured by the harness (FR-CIT: 100% in evaluation runs); the
  harness runs golden-set construction → run → report → review and produces
  metrics; datasets and thresholds come from OQ-06. Until harness results
  exist and are cited, the no-claims policy in the MVP scope stays in force.
- **Definition of done:** C-2 demonstrable; an evaluation report exists with
  cited results, or the no-claims policy remains explicitly in force.
- **Depends on:** M2 and M3; resolution of OQ-06.

### M5 — OHTTP integration

- **Goal:** the OHTTP-protected submission path live, integrated behind the
  transport abstraction of
  [ADR-0005](../adr/0005-ohttp-behind-transport-abstraction.md).
- **Testable outcomes:** all submission endpoints accept only encapsulated
  requests (C-7); the gateway enforces the default-deny target policy
  including private and metadata-address rejection; an independently operated
  relay topology exists (OQ-13); network-level egress control is deployed
  (OQ-09); gateway key lifecycle operations run (OQ-08); the dependency is
  commit-pinned with a documented contingency (OQ-03, OQ-15).
- **Definition of done:** C-7 demonstrable in a deployment that matches the
  recorded trust model ([ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md)).
- **Depends on:** M2; resolution of OQ-03, OQ-08, OQ-09, OQ-10, OQ-13, OQ-15.

### M6 — MCP surface and hardening

- **Goal:** the full scoped tool surface and pre-release hardening.
- **Testable outcomes:** tools are exposed through the scoped MCP surface
  with per-tool authorization independent of the model; the SDK Tier-3
  upgrade policy is applied (OQ-04); retention periods and deletion SLAs are
  implemented per OQ-11; the deferred threat model is executed (OQ-12);
  operational visibility is complete (C-8).
- **Definition of done:** all eight capabilities (C-1 through C-8) are
  demonstrable together; open register items are closed or accepted by the
  human owner with recorded rationale.
- **Depends on:** M3, M4, M5; resolution of OQ-04, OQ-11, OQ-12, OQ-14.

## Decision traceability table

Each human-fixed decision maps to **exactly one** authoritative location.

| ID | Decision (one-line normative statement) | Authoritative location | Supporting references |
|---|---|---|---|
| D-1 | The system is built initially as a modular monolith. | [ADR-0001](../adr/0001-modular-monolith-first.md#decision) | [System design: System overview](../architecture/system-design.md#system-overview) |
| D-2 | PostgreSQL is the single source of truth for all durable product state; derived stores are rebuildable. | [ADR-0002](../adr/0002-postgresql-source-of-truth.md#decision) | [System design: Persistence module](../architecture/system-design.md#modules-and-responsibilities) |
| D-3 | Semantic memory, workflow checkpoints, and conversation history are distinct constructs with distinct lifecycles; they are never interchangeable. | [ADR-0003](../adr/0003-separated-ai-state-persistence.md#decision) | [System design: Memory and state constructs](../architecture/system-design.md#memory-and-state-constructs) |
| D-4 | Retrieval evaluation must be defined and measurable before any retrieval-quality claim is made. | [MVP scope: Retrieval quality policy and evaluation design](../product/mvp-scope.md#retrieval-quality-policy-and-evaluation-design) | [Glossary: Retrieval evaluation](../product/glossary.md#retrieval-evaluation) |
| D-5 | OHTTP protects the client-to-gateway boundary only — not end-to-end to any AI provider. | [ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md#decision) | [System design: OHTTP integration and trust model](../architecture/system-design.md#ohttp-integration-and-trust-model) |
| D-6 | OHTTP integration sits behind an application-facing transport abstraction. | [ADR-0005](../adr/0005-ohttp-behind-transport-abstraction.md#decision) | [ADR-0004](../adr/0004-ohttp-scope-client-to-gateway.md) |
| D-7 | The runtime orchestration approach is intentionally undecided; a bounded in-process agent loop is the working MVP default. | [ADR-0006](../adr/0006-ai-runtime-orchestration-deferred.md#decision) | [System design: Conversation and bounded agent loop](../architecture/system-design.md#conversation-and-bounded-agent-loop-mcp-interaction) |
| D-8 | MCP exposes scoped capabilities; every tool invocation passes application authorization independent of model decisions. | [ADR-0007](../adr/0007-mcp-capability-boundary.md#decision) | [MVP scope: FR-MCP](../product/mvp-scope.md#functional-requirements) |
| D-9 | No infrastructure is introduced without a current, concrete, stated need. | [ADR-0001, Rationale](../adr/0001-modular-monolith-first.md#rationale)* | [MVP non-goals](../product/mvp-scope.md#mvp-non-goals) |
| D-10 | Privacy, tenant isolation, prompt injection, outbound restrictions, retention, and deletion are day-one architectural concerns. | [System design: Privacy and data protection requirements](../architecture/system-design.md#privacy-and-data-protection-requirements) | [ADR-0002](../adr/0002-postgresql-source-of-truth.md), [ADR-0003](../adr/0003-separated-ai-state-persistence.md) |
| D-11 | Users can inspect, correct, and delete persistent memories about them, including derived data. | [MVP scope: FR-MEM](../product/mvp-scope.md#functional-requirements) | [ADR-0003](../adr/0003-separated-ai-state-persistence.md) |
| D-12 | Citations reference evidence actually retrieved for the answer; model-invented references never appear. | [MVP scope: FR-CIT](../product/mvp-scope.md#functional-requirements) | [MVP scope: C-2](../product/mvp-scope.md#capability-table) |

\* Note: the same principle appears in `AGENTS.md` as daily engineering
guidance; that file is guidance, not the decision record — the authoritative
location for D-9 is the ADR section linked above.

## Open questions and assumptions register

Register entries are never deleted; they are closed with a resolution note.

| ID | Question | Impact if unresolved | Proposed next step | Status |
|---|---|---|---|---|
| OQ-01 | Which tenancy and deployment topology applies (single-tenant deployment per customer, or multi-tenant deployment)? Tenant data isolation is a hard requirement either way; only the topology is undecided. | Determines whether isolation is enforced at the schema level or the deployment level; blocks M2 storage design. | Decide in an architecture review with the human owner before M2; record the outcome as an ADR. | Open |
| OQ-02 | Symfony version line: 7.4 LTS or the 8.x line? | Fixes the PHP floor (8.2 vs 8.4) and the support window; blocks the M1 skeleton bootstrap. | Decide at M1 start using the verified fact-set in the [technology stack](../architecture/system-design.md#technology-stack); record as a short ADR. | Open |
| OQ-03 | Commit-pin strategy and adoption gate for the release-less OHTTP candidate library. | An unpinned, release-less dependency is an uncontrolled supply chain; blocks M5. | Security-review the single upstream commit; define the commit-pin and acceptance tests before M5. | Open |
| OQ-04 | Upgrade and maturity policy for the Tier-3 MCP PHP SDK. | The youngest SDK tier carries breaking-protocol churn risk for the tool surface; blocks M6. | Define version-pinning and upgrade monitoring at M6 entry. | Open |
| OQ-05 | Which AI provider and embedding model? | Gates real model calls in M3 (M3 proceeds on test adapters; real calls stay gated); the embedding-model choice fixes embedding dimensions, which M2's vector schema needs (the vector capability itself remains Proposed per [ADR-0002](../adr/0002-postgresql-source-of-truth.md) pending M2 confirmation) before schema freeze; determines which provider retention/training terms must be recorded at the boundary. | Evaluate candidates behind the [ADR-0008](../adr/0008-provider-neutral-ai-boundary.md) seam, including their retention/training terms (zero-retention endpoints or user-facing disclosure); the human owner selects the provider and embedding model; embedding dimensions must be fixed before M2 schema freeze; provider selection gates M3 real model calls; record the choice and its privacy terms as an ADR. | Open |
| OQ-06 | Which concrete evaluation datasets and metric thresholds? | Without them the evaluation harness cannot run and the no-claims policy stays in force; blocks M4 completion. | Select licensed, tenant-representative, held-out datasets and thresholds with the human owner before the M4 harness executes; record them here with sources. | Open |
| OQ-07 | Which queue transport for asynchronous processing — including payload sensitivity (identifiers and references over content), broker encryption at rest, and broker access control? | Determines an infrastructure dependency and the failure semantics of async processing; blocks M2. | Decide together with OQ-01 at M2 planning; record here. | Open |
| OQ-08 | Gateway key management and rotation operations (generate, advertise, rotate, retire). | Key compromise or uncoordinated rotation breaks the submission path or its unlinkability; blocks M5 operations. | Design the key lifecycle operations and the authenticated key-distribution channel before M5. | Open |
| OQ-09 | Network-level egress control (required by the DNS-rebinding caveat). | Application-level target validation alone is insufficient; without network egress control the defense-in-depth requirement is unmet; blocks M5 deployment. | Specify network-policy egress rules in the deployment design before M5. | Open |
| OQ-10 | Replay-tracking hardening beyond the RFC's Date recommendation. | Untracked replays within the accepted window could re-execute captured requests; the risk level is unassessed. | Assess replay impact on this product's operations and decide whether to implement tracking before M5. | Open |
| OQ-11 | Retention periods and deletion service levels per construct — including failure/dead-letter retention bounds per transport; reconciliation of deletion with backup and restore cycles. | Without values, per-construct retention periods and operator-facing deletion SLAs beyond C-5's provisional test bound are undefined; this blocks M6 retention/deletion governance implementation and may affect M4 evaluation-run governance. | The human owner sets per-construct retention values and deletion SLAs; record them here and reference them from the product documents. | Open |
| OQ-12 | Scope and timing of the deferred threat model. | Attacker perspectives stay unexamined while features land; must not stay deferred past M6. | Schedule and scope the threat-model workshop after M4; record its outputs as security decisions; include operator/insider access and application-log content policy in the threat-model scope. | Open |
| OQ-13 | Hosting topology for the independently operated relay and gateway. | Meaningful unlinkability requires independent operation; without it the submission-path differentiator is hollow; blocks M5. | Identify the independent operator or hosting split and the operational contract before M5. | Open |
| OQ-14 | UI scope in the MVP (the proposed Next.js client). | Determines whether a web client is a deliverable or the API is the only interface; affects M3/M4 scope and the M6 full-surface demonstration; if the client is in scope, its delivery is added to the affected milestones' outcomes at decision time. | Product decision with the human owner before M3. | Open |
| OQ-15 | Contingency triggers for the single-maintainer OHTTP library (fork or replace). | An abandoned dependency could strand the OHTTP path; without defined triggers the contingency fires late. | Define activity and maintenance triggers and the fork decision path alongside OQ-03 before M5. | Open |
| OQ-16 | What is the authentication and session security baseline for the MVP (credential storage, session management, brute-force protection)? The scope note "minimal local accounts" carries no security baseline yet. | Without a decision, the account scaffolding M1 delivers and M3 user-facing features lack an auth baseline. | The human owner decides the auth FR baseline before M1; record it in the MVP scope requirements. | Open |
| OQ-17 | Is team/workspace-level sharing of documents or memories within a tenant in product scope? | Determines whether object-level authorization defaults (user-only visibility) are ever relaxed and how the FR-MEM exclusivity wording and the C-5 denial tests are scoped; resolution amends FR-MEM and C-5 explicitly. | Product decision before M3; the default until then is user-only visibility per the [privacy section](../architecture/system-design.md#privacy-and-data-protection-requirements). | Open |

## Validation strategy

The Increment 0 validation suite (V1–V18), each mapped to its acceptance
criteria. The M1 tooling encodes these checks in CI.

**Committed patterns and self-exclusion:** the search patterns in this
section are committed as runnable literals. A check that would otherwise
match its own source line excludes this file with
`--exclude=implementation-plan.md` and uses character-class self-exclusion in
the pattern (for example `vei[l]desk`, `Lang[G]raph`). Repo-wide uniqueness
checks (V14, V16) must scope to their target file: the marker and policy
sentences also appear inside the validation commands below, so only a check
scoped to its target file can assert "exactly once".

```bash
# V1 (AC-1): every deliverable exists.
for f in README.md docs/product/product-vision.md docs/product/mvp-scope.md \
         docs/product/glossary.md docs/architecture/system-design.md \
         docs/development/implementation-plan.md \
         docs/adr/0001-modular-monolith-first.md \
         docs/adr/0002-postgresql-source-of-truth.md \
         docs/adr/0003-separated-ai-state-persistence.md \
         docs/adr/0004-ohttp-scope-client-to-gateway.md \
         docs/adr/0005-ohttp-behind-transport-abstraction.md \
         docs/adr/0006-ai-runtime-orchestration-deferred.md \
         docs/adr/0007-mcp-capability-boundary.md \
         docs/adr/0008-provider-neutral-ai-boundary.md; do
  test -f "$f" && echo "OK $f" || echo "MISSING $f"
done

# V2 (AC-17): zero occurrences of the previous working name under README.md
# and docs/. Self-exclusion: --exclude plus the [l] character class keep this
# file from matching its own check.
grep -RInIi --exclude=implementation-plan.md "vei[l]desk" README.md docs/ ; echo "exit=$?"   # expect no output, exit 1 (case-insensitive)

# V3 (AC-17): repo-wide (excluding .git), only AGENTS.md mentions the previous
# working name — in its naming-prohibition sentence (case-insensitive).
grep -RIil --exclude=implementation-plan.md "vei[l]desk" . --exclude-dir=.git   # expect exactly ./AGENTS.md

# V4 (AC-14): the deferred orchestration framework name appears only in
# ADR-0006 (any count) and the pre-existing negative mention at
# docs/engineering/agent-workflow.md line 109.
grep -RIn --exclude-dir=.git --exclude=implementation-plan.md "Lang[G]raph" .

# V5 (AC-14): ADR-0006 structure — Status: Open, exactly three option bullets
# matching the option-label pattern, and citations naming both conflict sites.
grep -q "^Status: Open" docs/adr/0006-ai-runtime-orchestration-deferred.md
test "$(grep -cE '^- \*\*Option \([abc]\) — .*(bounded in-process|Lang[G]raph|PHP-native graph)' docs/adr/0006-ai-runtime-orchestration-deferred.md)" = "3"   # expect exit 0
grep -q "AGENTS.md" docs/adr/0006-ai-runtime-orchestration-deferred.md
grep -q "agent-workflow.md" docs/adr/0006-ai-runtime-orchestration-deferred.md

# V6 (AC-14/non-goals): guards the sanctioned-edit scope and the non-goals —
# only new files plus the two sanctioned edits changed.
git diff --name-only 595b1d5
git diff 595b1d5 -- AGENTS.md docs/engineering/agent-workflow.md

# V7 (AC-1, AC-18): relative .md link integrity across README.md and docs/.
# The file-existence part is normative; the anchor part (V7b) is best-effort —
# see the limitation note below.
broken=0
while IFS= read -r f; do
  d=$(dirname "$f")
  for link in $(grep -oE '\]\([^)#]+\.md' "$f" | sed -E 's/^\]\(//' | sort -u); do
    if [ ! -f "$d/$link" ]; then echo "BROKEN $f -> $link"; broken=1; fi
  done
  # V7b (best-effort anchor check): for every .md link that carries a
  # fragment, verify a matching heading exists in the target file, using a
  # GitHub-style slug approximation
  # (lowercase; strip characters other than letters, digits, spaces, hyphens;
  # spaces to hyphens). Limitation: GitHub's slugger also transforms emoji and
  # some punctuation differently; the PR preview remains the authority for
  # anchor rendering.
  while IFS= read -r link; do
    target="${link%%#*}"; frag="${link##*#}"
    [ -f "$d/$target" ] || continue
    grep -E '^#{1,6} ' "$d/$target" | sed -E 's/^#{1,6} //; s/[^A-Za-z0-9 -]//g' \
      | tr '[:upper:]' '[:lower:]' | sed -E 's/ /-/g' | grep -qx -- "$frag" \
      || echo "ANCHOR-MISMATCH $f -> $link"
  done < <(grep -oE '\]\([^)#]+\.md#[a-z0-9-]+\)' "$f" | sed -E 's/^\]\(//; s/\)$//' | sort -u)
done < <(find README.md docs -name '*.md' -type f)
echo "broken=$broken"   # expect broken=0 and no ANCHOR-MISMATCH lines

# V8 (AC-15): six Mermaid diagrams, even fence count in system-design.md.
grep -c '^```mermaid' docs/architecture/system-design.md    # expect 6
fences=$(grep -c '^```' docs/architecture/system-design.md); echo "fences=$fences"  # expect even

# V9 (AC-15, AC-9): required OHTTP phrasings in system-design.md.
grep -c "relay cannot read" docs/architecture/system-design.md            # expect >= 1
grep -c "gateway sees content by design" docs/architecture/system-design.md  # expect >= 1
grep -c "independently operated" docs/architecture/system-design.md       # expect >= 1

# V10 (AC-18): every new document and README carries the dated header line.
for f in README.md docs/product/product-vision.md docs/product/mvp-scope.md \
         docs/product/glossary.md docs/architecture/system-design.md \
         docs/development/implementation-plan.md docs/adr/000[1-8]-*.md; do
  grep -q "^Date: 2026-10-24" "$f" && echo "OK $f" || echo "MISSING DATE $f"
done

# V11 (AC-11): the verified date appears at least 5 times in system-design.md.
grep -o "2026-10-24" docs/architecture/system-design.md | wc -l   # expect >= 5

# V12 (AC-2): exactly 12 decision rows in the traceability table.
grep -cE '^\| D-' docs/development/implementation-plan.md          # expect 12

# V13 (AC-16): the register has exactly the contiguous rows OQ-01..OQ-17 and
# no row has an empty cell.
test "$(grep -cE '^\| OQ-' docs/development/implementation-plan.md)" = "17"   # expect exit 0
tmp=$(mktemp -d)
grep -oE '^\| OQ-[0-9]{2}' docs/development/implementation-plan.md | cut -d- -f2 | sort > "$tmp/got"
seq -f '%02g' 1 17 > "$tmp/want"
diff "$tmp/got" "$tmp/want"   # expect no output
grep -E '^\| OQ-' docs/development/implementation-plan.md | grep -E '\|\s*\|' ; echo "empty-cell-exit=$?"  # expect exit 1

# V14 (AC-8): the five construct terms and the exact marker sentence (once).
for t in "Short-term conversation context" "Episodic memory" \
         "Long-term approved memory" "Workflow checkpoint" "Conversation history"; do
  grep -q "$t" docs/architecture/system-design.md && echo "OK $t" || echo "MISSING $t"
done
grep -cF "These five constructs are distinct by design; no construct may be implemented as, substituted by, or merged into another, and no data may migrate between them without an explicit reviewed decision." docs/architecture/system-design.md  # expect 1

# V15 (AC-4): no capability row has an empty criterion cell, plus an advisory
# heuristic: each criterion cell should contain a digit, a percent sign, or a
# latency/time unit (min/hour/ms/P95). The bare-s unit was dropped from the
# heuristic as too loose to be meaningful. This is a heuristic only —
# criterion semantics still require human review.
grep -cE '^\| C-' docs/product/mvp-scope.md   # expect 8
grep -E '^\| C-' docs/product/mvp-scope.md | grep -E '\|\s*\|' ; echo "empty-cell-exit=$?"  # expect exit 1
grep -E '^\| C-' docs/product/mvp-scope.md | awk -F'|' '$5 !~ /[0-9%]|min|hour|ms|P95/ {print "HEURISTIC-REVIEW:", $2}' ; echo "heuristic done"  # expect no HEURISTIC-REVIEW lines

# V16 (AC-5): the exact no-claims policy sentence appears in mvp-scope.md.
grep -cF "No retrieval-quality or answer-quality claim may be published anywhere in this repository until evaluation results produced by the procedure below exist and are cited." docs/product/mvp-scope.md  # expect 1

# V17 (AC-9, AC-10, advisory): no overclaiming language under README.md and
# docs/. Self-exclusion keeps this file from matching its own pattern.
grep -RInE --exclude=implementation-plan.md "is end-to-end|anonymous|untraceable|fully private" README.md docs/ ; echo "exit=$?"  # expect no output, exit 1

# V18 (AC-17, advisory): no quality adjectives in README.md.
grep -InE "fast|scalable|secure|powerful|production-ready|best-in-class" README.md ; echo "exit=$?"  # expect no output, exit 1
```

Mermaid rendering cannot be verified offline in this increment; the six
diagrams require a manual render check (GitHub PR preview) by the
docs-reviewer.

## Assumptions

- External facts: any fact outside the research dossier (verified
  2026-10-24) that informs a design choice carries a Proposed or Open status
  and a register row; primary-source normative citations (for example
  RFC 9458) are linked directly where used. The dossier itself is an external
  working artifact of the coordinator; the stack table's per-row sources
  make each claim independently verifiable.
- All documents are normative specifications until superseded by a reviewed
  decision.
- Mermaid diagrams are validated by human review in the GitHub PR preview;
  no offline renderer ran in this increment.
- The validation suite above is committed as runnable literals and is the
  seed of the M1 CI tooling; its patterns are carried over unchanged.
- The date 2026-10-24 is fixed for every document in this increment.

## Links

- [Product vision](../product/product-vision.md)
- [MVP scope](../product/mvp-scope.md)
- [System design](../architecture/system-design.md)
- [Glossary](../product/glossary.md)
- [ADR-0001](../adr/0001-modular-monolith-first.md) through [ADR-0008](../adr/0008-provider-neutral-ai-boundary.md)
