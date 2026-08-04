# Contract baseline

This directory contains derived metadata used to plan and verify SDK coverage. It does not redistribute the official OpenAPI documents.

- `contract-lock.json` records official source URLs, versions, normalized hashes, and the redistribution safeguard.
- `operations.json` is the generated inventory of 42 production operations and their implementation phases.
- `implementation-coverage.json` maps the 4 implemented phase 5 operations to their public SDK methods (4/42 total).
- `prod-next-diff.json` classifies the current production-to-next contract changes.
- `validation-rules.json` records announced API 1.6 constraints without treating the planned release as deployed.
- `pending-stage-verifications.json` is the deferred integration checklist and release gate.
- `formal-decisions.json` records adopted decisions and outstanding formal confirmation.

Regenerate and validate these artifacts with the [contract tools](../../tools/contract/README.md). The official source URLs and analysis date are locked in `contract-lock.json`.
