#!/usr/bin/env bash
#
# AegisThread documentation validation suite (V1–V18).
#
# Encodes the validation strategy from
# docs/development/implementation-plan.md ("Validation strategy") as adapted
# for M1 on 2026-10-25: V6, V10, and V12 in their adapted forms; V1–V5, V7–V9,
# V11, and V13–V18 with the Increment 0 literals unchanged.
#
# Usage: run from the repository root (the script re-roots itself relative to
# its own location, so invoking it by path from elsewhere works too):
#
#   bash tools/validate-docs.sh
#
# Semantics:
#   - Every check prints PASS or FAIL and appears in the summary.
#   - V6 is advisory: it always reports PASS and prints its diff listing for
#     reviewer visibility.
#   - V15's heuristic section is advisory: it prints HEURISTIC-REVIEW lines
#     but does not fail the run.
#   - Checks that "expect no output" (V2, V13 empty-cell probe, V15 empty-cell
#     probe, V17, V18) are implemented with inverted exit logic.
#   - V7's file-existence part is normative; its anchor part (V7b) is
#     best-effort and reported without failing the run (the GitHub PR preview
#     remains the anchor-render authority).
#   - Any normative FAIL exits nonzero and prints DOC-VALIDATION: FAIL.

set -u -o pipefail

cd "$(dirname "$0")/.." || { echo "cannot locate repository root"; exit 1; }

CHECK_NAMES=()
CHECK_RESULTS=()

record() { # record <ID> <PASS|FAIL> [note]
  CHECK_NAMES+=("$1")
  CHECK_RESULTS+=("$2")
  if [ "$2" = "PASS" ]; then
    echo "PASS $1${3:+ — $3}"
  else
    echo "FAIL $1${3:+ — $3}"
  fi
}

# V1 (AC-1): every deliverable exists.
echo "--- V1 (AC-1): every deliverable exists ---"
v1_missing=0
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
  test -f "$f" && echo "OK $f" || { echo "MISSING $f"; v1_missing=1; }
done
[ "$v1_missing" -eq 0 ] && record V1 PASS || record V1 FAIL "missing deliverable(s) listed above"

# V2 (AC-17): zero occurrences of the previous working name under README.md
# and docs/. Self-exclusion: --exclude plus the [l] character class keep the
# plan file from matching its own check. Inverted exit logic: PASS means grep
# found nothing (exit 1).
echo "--- V2 (AC-17): zero occurrences of the previous working name under README.md and docs/ ---"
v2_out=$(grep -RInIi --exclude=implementation-plan.md "vei[l]desk" README.md docs/)
v2_rc=$?
if [ "$v2_rc" -eq 1 ] && [ -z "$v2_out" ]; then
  record V2 PASS
else
  [ -n "$v2_out" ] && printf '%s\n' "$v2_out"
  record V2 FAIL "previous working name found (grep exit $v2_rc)"
fi

# V3 (AC-17): repo-wide (excluding .git), only AGENTS.md mentions the previous
# working name — in its naming-prohibition sentence (case-insensitive).
echo "--- V3 (AC-17): only AGENTS.md mentions the previous working name repo-wide ---"
v3_out=$(grep -RIil --exclude=implementation-plan.md "vei[l]desk" . --exclude-dir=.git)
if [ "$v3_out" = "./AGENTS.md" ]; then
  record V3 PASS
else
  [ -n "$v3_out" ] && printf '%s\n' "$v3_out"
  record V3 FAIL "expected exactly ./AGENTS.md"
fi

# V4 (AC-14): the deferred orchestration framework name appears only in
# ADR-0006 (any count) and the pre-existing negative mention at
# docs/engineering/agent-workflow.md line 109.
echo "--- V4 (AC-14): deferred orchestration framework name only in ADR-0006 and agent-workflow.md ---"
v4_out=$(grep -RIn --exclude-dir=.git --exclude=implementation-plan.md "Lang[G]raph" . || true)
v4_files=$(printf '%s\n' "$v4_out" | sed -E 's/:[0-9]+:.*//' | sort -u)
v4_expected=$(printf '%s\n' \
  "./docs/adr/0006-ai-runtime-orchestration-deferred.md" \
  "./docs/engineering/agent-workflow.md" | sort)
[ -n "$v4_out" ] && printf '%s\n' "$v4_files"
if [ "$v4_files" = "$v4_expected" ]; then
  record V4 PASS
else
  record V4 FAIL "match sites differ from ADR-0006 + agent-workflow.md"
fi

# V5 (AC-14): ADR-0006 structure — Status: Open, exactly three option bullets
# matching the option-label pattern, and citations naming both conflict sites.
echo "--- V5 (AC-14): ADR-0006 structure ---"
v5_ok=1
grep -q "^Status: Open" docs/adr/0006-ai-runtime-orchestration-deferred.md \
  || { echo "MISSING: ^Status: Open"; v5_ok=0; }
v5_opts=$(grep -cE '^- \*\*Option \([abc]\) — .*(bounded in-process|Lang[G]raph|PHP-native graph)' \
  docs/adr/0006-ai-runtime-orchestration-deferred.md)
echo "option-bullets=$v5_opts"
[ "$v5_opts" = "3" ] || { echo "expected 3 option bullets"; v5_ok=0; }
grep -q "AGENTS.md" docs/adr/0006-ai-runtime-orchestration-deferred.md \
  || { echo "MISSING citation: AGENTS.md"; v5_ok=0; }
grep -q "agent-workflow.md" docs/adr/0006-ai-runtime-orchestration-deferred.md \
  || { echo "MISSING citation: agent-workflow.md"; v5_ok=0; }
[ "$v5_ok" -eq 1 ] && record V5 PASS || record V5 FAIL "details above"

# V6 (AC-14/non-goals, advisory): adapted for M1, 2026-10-25 — the M0 anchor
# commit 595b1d5 guarded Increment 0's sanctioned-edit scope, which is
# complete; replaced (advisory) by a merge-base diff listing for reviewer
# visibility. This check always reports PASS; its listing is informational.
echo "--- V6 (advisory): changed files since merge base ---"
base=$(git merge-base origin/main HEAD 2>/dev/null || echo HEAD~1)
echo "merge-base: $base"
git diff --name-only "$base" || true
record V6 PASS "advisory — listing above is for reviewer visibility, non-blocking"

# V7 (AC-1, AC-18): relative .md link integrity across README.md and docs/.
# The file-existence part is normative; the anchor part (V7b) is best-effort —
# anchor mismatches are printed but do not fail the run (GitHub-style slug
# approximation; the PR preview remains the authority for anchor rendering).
echo "--- V7 (AC-1, AC-18): relative .md link integrity across README.md and docs/ ---"
broken=0
anchors_bad=0
while IFS= read -r f; do
  d=$(dirname "$f")
  for link in $(grep -oE '\]\([^)#]+\.md' "$f" | sed -E 's/^\]\(//' | sort -u); do
    if [ ! -f "$d/$link" ]; then echo "BROKEN $f -> $link"; broken=1; fi
  done
  # V7b (best-effort anchor check): for every .md link that carries a
  # fragment, verify a matching heading exists in the target file, using a
  # GitHub-style slug approximation (lowercase; strip characters other than
  # letters, digits, spaces, hyphens; spaces to hyphens).
  while IFS= read -r link; do
    target="${link%%#*}"; frag="${link##*#}"
    [ -f "$d/$target" ] || continue
    grep -E '^#{1,6} ' "$d/$target" | sed -E 's/^#{1,6} //; s/[^A-Za-z0-9 -]//g' \
      | tr '[:upper:]' '[:lower:]' | sed -E 's/ /-/g' | grep -qx -- "$frag" \
      || { echo "ANCHOR-MISMATCH $f -> $link"; anchors_bad=1; }
  done < <(grep -oE '\]\([^)#]+\.md#[a-z0-9-]+\)' "$f" | sed -E 's/^\]\(//; s/\)$//' | sort -u)
done < <(find README.md docs -name '*.md' -type f)
echo "broken=$broken anchors_bad=$anchors_bad"
if [ "$broken" -eq 0 ]; then
  if [ "$anchors_bad" -eq 0 ]; then
    record V7 PASS
  else
    record V7 PASS "advisory — ANCHOR-MISMATCH lines above are best-effort; verify in the PR preview"
  fi
else
  record V7 FAIL "broken relative .md links listed above"
fi

# V8 (AC-15): six Mermaid diagrams, even fence count in system-design.md.
echo "--- V8 (AC-15): six Mermaid diagrams, even fence count ---"
v8_mermaid=$(grep -c '^```mermaid' docs/architecture/system-design.md)
v8_fences=$(grep -c '^```' docs/architecture/system-design.md)
echo "mermaid=$v8_mermaid fences=$v8_fences"
if [ "$v8_mermaid" -eq 6 ] && [ $((v8_fences % 2)) -eq 0 ]; then
  record V8 PASS
else
  record V8 FAIL "expected mermaid=6 and an even fence count"
fi

# V9 (AC-15, AC-9): required OHTTP phrasings in system-design.md.
echo "--- V9 (AC-15, AC-9): required OHTTP phrasings ---"
v9_a=$(grep -c "relay cannot read" docs/architecture/system-design.md)
v9_b=$(grep -c "gateway sees content by design" docs/architecture/system-design.md)
v9_c=$(grep -c "independently operated" docs/architecture/system-design.md)
echo "relay-cannot-read=$v9_a gateway-sees-content-by-design=$v9_b independently-operated=$v9_c"
if [ "$v9_a" -ge 1 ] && [ "$v9_b" -ge 1 ] && [ "$v9_c" -ge 1 ]; then
  record V9 PASS
else
  record V9 FAIL "each phrasing must appear at least once"
fi

# V10 (AC-18): every new document and README carries the dated header line.
# Adapted for M1, 2026-10-25: the pattern accepts Increment 0 documents dated
# 2026-10-24 and M1 documents dated 2026-10-25, and the file list is extended
# with ADR-0009 (glob widened to 000[1-9]) and docs/development/setup.md.
echo "--- V10 (AC-18): dated header line on every new document and README ---"
v10_bad=0
for f in README.md docs/product/product-vision.md docs/product/mvp-scope.md \
         docs/product/glossary.md docs/architecture/system-design.md \
         docs/development/implementation-plan.md docs/adr/000[1-9]-*.md \
         docs/development/setup.md; do
  grep -qE "^Date: 2026-10-(24|25)" "$f" && echo "OK $f" || { echo "MISSING DATE $f"; v10_bad=1; }
done
[ "$v10_bad" -eq 0 ] && record V10 PASS || record V10 FAIL "files without dated header listed above"

# V11 (AC-11): the verified date appears at least 5 times in system-design.md.
# Not adapted for M1 (2026-10-25): the count remains >= 5 after the M1 edits —
# the PHP, Symfony, Next.js, and CI-tooling rows were re-dated (the pgvector
# row gained a Verified 2026-10-25 deployment fact); every row verified on
# 2026-10-24 keeps that date.
echo "--- V11 (AC-11): verified date appears >= 5 times in system-design.md ---"
v11_count=$(grep -o "2026-10-24" docs/architecture/system-design.md | wc -l)
echo "count=$v11_count"
[ "$v11_count" -ge 5 ] && record V11 PASS || record V11 FAIL "expected >= 5"

# V12 (AC-2): exactly 14 decision rows in the traceability table.
# Adapted for M1, 2026-10-25: rows D-13 (ADR-0009, OQ-02 closure) and D-14
# (OQ-14 closure) were appended, raising the expected count from 12 to 14.
echo "--- V12 (AC-2): exactly 14 decision rows in the traceability table ---"
v12_count=$(grep -cE '^\| D-' docs/development/implementation-plan.md)
echo "count=$v12_count"
[ "$v12_count" -eq 14 ] && record V12 PASS || record V12 FAIL "expected 14"

# V13 (AC-16): the register has exactly the contiguous rows OQ-01..OQ-17 and
# no row has an empty cell.
echo "--- V13 (AC-16): register rows OQ-01..OQ-17 contiguous, no empty cells ---"
v13_total=$(grep -cE '^\| OQ-' docs/development/implementation-plan.md)
echo "total=$v13_total"
tmp=$(mktemp -d)
grep -oE '^\| OQ-[0-9]{2}' docs/development/implementation-plan.md | cut -d- -f2 | sort > "$tmp/got"
seq -f '%02g' 1 17 > "$tmp/want"
v13_diff_out=$(diff "$tmp/got" "$tmp/want")
v13_diff_rc=$?
[ -n "$v13_diff_out" ] && printf '%s\n' "$v13_diff_out"
grep -E '^\| OQ-' docs/development/implementation-plan.md | grep -E '\|\s*\|'
v13_empty_rc=$?
rm -rf "$tmp"
if [ "$v13_total" -eq 17 ] && [ "$v13_diff_rc" -eq 0 ] && [ "$v13_empty_rc" -eq 1 ]; then
  record V13 PASS
else
  record V13 FAIL "total=$v13_total diff_rc=$v13_diff_rc empty_cell_grep_exit=$v13_empty_rc (expect 17/0/1)"
fi

# V14 (AC-8): the five construct terms and the exact marker sentence (once).
echo "--- V14 (AC-8): five construct terms and the exact marker sentence (once) ---"
v14_ok=1
for t in "Short-term conversation context" "Episodic memory" \
         "Long-term approved memory" "Workflow checkpoint" "Conversation history"; do
  grep -q "$t" docs/architecture/system-design.md && echo "OK $t" || { echo "MISSING $t"; v14_ok=0; }
done
v14_marker=$(grep -cF "These five constructs are distinct by design; no construct may be implemented as, substituted by, or merged into another, and no data may migrate between them without an explicit reviewed decision." docs/architecture/system-design.md)
echo "marker=$v14_marker"
[ "$v14_marker" -eq 1 ] || { echo "expected marker exactly once"; v14_ok=0; }
[ "$v14_ok" -eq 1 ] && record V14 PASS || record V14 FAIL "details above"

# V15 (AC-4): no capability row has an empty criterion cell, plus an advisory
# heuristic: each criterion cell should contain a digit, a percent sign, or a
# latency/time unit (min/hour/ms/P95). The heuristic section prints
# HEURISTIC-REVIEW lines but never fails the run (advisory).
echo "--- V15 (AC-4): capability rows — no empty criterion cell (+ advisory heuristic) ---"
v15_count=$(grep -cE '^\| C-' docs/product/mvp-scope.md)
echo "capability-rows=$v15_count"
v15_ok=1
[ "$v15_count" -eq 8 ] || { echo "expected 8 capability rows"; v15_ok=0; }
grep -E '^\| C-' docs/product/mvp-scope.md | grep -E '\|\s*\|'
v15_empty_rc=$?
[ "$v15_empty_rc" -eq 1 ] || { echo "empty cell found (grep exit $v15_empty_rc)"; v15_ok=0; }
grep -E '^\| C-' docs/product/mvp-scope.md | awk -F'|' '$5 !~ /[0-9%]|min|hour|ms|P95/ {print "HEURISTIC-REVIEW:", $2}'
echo "heuristic done"
[ "$v15_ok" -eq 1 ] && record V15 PASS "heuristic section is advisory" || record V15 FAIL "details above"

# V16 (AC-5): the exact no-claims policy sentence appears in mvp-scope.md.
echo "--- V16 (AC-5): exact no-claims policy sentence appears once ---"
v16_count=$(grep -cF "No retrieval-quality or answer-quality claim may be published anywhere in this repository until evaluation results produced by the procedure below exist and are cited." docs/product/mvp-scope.md)
echo "count=$v16_count"
[ "$v16_count" -eq 1 ] && record V16 PASS || record V16 FAIL "expected exactly 1"

# V17 (AC-9, AC-10): no overclaiming language under README.md and docs/.
# Self-exclusion keeps the plan file from matching its own pattern. Inverted
# exit logic: PASS means grep found nothing (exit 1).
echo "--- V17 (AC-9, AC-10): no overclaiming language under README.md and docs/ ---"
v17_out=$(grep -RInE --exclude=implementation-plan.md "is end-to-end|anonymous|untraceable|fully private" README.md docs/)
v17_rc=$?
if [ "$v17_rc" -eq 1 ] && [ -z "$v17_out" ]; then
  record V17 PASS
else
  [ -n "$v17_out" ] && printf '%s\n' "$v17_out"
  record V17 FAIL "overclaiming language found (grep exit $v17_rc)"
fi

# V18 (AC-17): no quality adjectives in README.md. Inverted exit logic: PASS
# means grep found nothing (exit 1).
echo "--- V18 (AC-17): no quality adjectives in README.md ---"
v18_out=$(grep -InE "fast|scalable|secure|powerful|production-ready|best-in-class" README.md)
v18_rc=$?
if [ "$v18_rc" -eq 1 ] && [ -z "$v18_out" ]; then
  record V18 PASS
else
  [ -n "$v18_out" ] && printf '%s\n' "$v18_out"
  record V18 FAIL "quality adjectives found (grep exit $v18_rc)"
fi

# Summary: per-check PASS/FAIL and a nonzero exit code if any normative check
# failed (V6 and the V15 heuristic are advisory).
echo
echo "=== Validation summary ==="
fail_count=0
i=0
for name in "${CHECK_NAMES[@]}"; do
  result="${CHECK_RESULTS[$i]}"
  echo "$result $name"
  [ "$result" = "FAIL" ] && fail_count=$((fail_count + 1))
  i=$((i + 1))
done
echo
if [ "$fail_count" -eq 0 ]; then
  echo "DOC-VALIDATION: PASS"
  exit 0
else
  echo "DOC-VALIDATION: FAIL ($fail_count check(s) failing)"
  exit 1
fi
