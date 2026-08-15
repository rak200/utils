# RFC 0017, rollout step 5 — the secret-scanning canary. NOT FOR MERGE.

Synthetic secrets, in shapes **measured against gitleaks 8.30.1 before being planted**. They
authenticate nothing and belong to no service.

Expected: **2 findings**, both `generic-api-key`.

## Why these shapes and not a credential

The first version of this fixture planted an AWS access key pair, and it never reached CI:
**GitHub push protection rejected the push at the remote**, naming
`Amazon AWS Access Key ID` and `Amazon AWS Secret Access Key`, before any workflow existed
to run. A third control, outside this pipeline entirely, which RFC 0017 neither configures
nor mentions.

That is the moving-alias finding again — a canary shadowed by a gate that runs earlier —
with the shadowing gate now off the estate's own map. And it says something about the
layers rather than only about the test: **push protection and the `gitleaks` step are not
redundant, they are complementary.** Push protection covers high-confidence partner patterns
and stops them at the push; gitleaks' default ruleset is broader — `generic-api-key`,
private keys, things belonging to no provider — and the CI step is the layer that catches
precisely what the first one does not look for.

So the fixture that tests the CI step must be a secret push protection has no pattern for.
Measured, from a probe run before planting: `api_key = "…"` and a generic
`*_secret = "…"` assignment both hit `generic-api-key`; a basic-auth URL was **not**
detected at all; AWS's own published example key, `AKIAIOSFODNN7EXAMPLE`, is allowlisted and
is not detected either. Plant the shape that was measured.

## The two controls

1. **The local pre-push hook** (`.githooks/pre-push`, preventive) — already fired, on the
   AWS version of this fixture: `1 commits scanned`, `leaks found: 2`,
   `Nothing was pushed`, and the branch verified absent from the remote afterwards.
2. **The `gitleaks` step in `base.yml`** (detective) — must redden `ci / gate`. It is the
   last step of the `conformance` job, so any earlier failure skips it and masks this canary.

```
api_key = "kQ7pLm2XvR8tNc4WfZs1YbHj5AeDgUiO9TrPxMnB"
internal_service_secret = "8fT2xQvB6nR4mK9wZs3YdL7pAe1CgUiO5TrJxHnV"
```
