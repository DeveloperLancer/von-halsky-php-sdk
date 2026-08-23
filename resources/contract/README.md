# Contract baseline

This directory contains derived metadata used to plan and verify SDK coverage. It does not redistribute the official OpenAPI documents.

- `contract-lock.json` records official source URLs, versions, normalized hashes, and the redistribution safeguard.
- `operations.json` is the generated inventory of 43 production operations and their implementation phases.
- `implementation-coverage.json` maps the 40 currently supported operations to their public SDK methods (40/43 total); the remaining three production operations are intentionally unsupported.
- `prod-next-diff.json` classifies the current production-to-next contract changes, including any breaking changes.
- `validation-rules.json` records constraints verified against the deployed production contract.

The `scope` and `scopes` entries in `operations.json` are informational. Callers select OAuth scopes when obtaining a token; the SDK does not enforce them at request time.
- `pending-stage-verifications.json` is the deferred integration checklist and release gate.
- `formal-decisions.json` records adopted decisions and outstanding formal confirmation.

Regenerate and validate these artifacts with the [contract tools](../../tools/contract/README.md). The official source URLs and analysis date are locked in `contract-lock.json`.
