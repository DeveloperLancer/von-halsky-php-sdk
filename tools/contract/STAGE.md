# Stage verification procedure

Stage verification is intentionally deferred until a dedicated test organization and credentials are available. The source of truth for outstanding checks is `resources/contract/pending-stage-verifications.json`.

## Credentials

Use a protected GitHub Environment named `stage`. Configure only the variables required by a test run:

- `VON_HALSKY_STAGE_CLIENT_ID`
- `VON_HALSKY_STAGE_CLIENT_SECRET`
- `VON_HALSKY_STAGE_REFRESH_TOKEN`
- `VON_HALSKY_STAGE_ACCESS_TOKEN`, for direct resource smoke tests
- `VON_HALSKY_STAGE_ORGANIZATION_ID`
- `VON_HALSKY_STAGE_LEAF_CATEGORY_ID`
- `VON_HALSKY_STAGE_PRODUCT_EAN`
- `VON_HALSKY_STAGE_ALLOW_WRITES`, defaulting to `0`

Never store values in repository files, workflow logs, test output, fixtures, or recorded HTTP traffic. Stage tests must reject the production API hostname and must remain in the PHPUnit `stage` group, which is excluded from the default test suite.

## Grant and rotation

1. Create credentials scoped to the dedicated Stage organization and the smallest usable permissions.
2. Add values to the protected `stage` environment through GitHub repository settings; require reviewer approval for workflows using it.
3. Record the grant date and responsible maintainer outside the repository without copying secret values.
4. Execute read-only checks first. Enable writes for an approved run only by setting `VON_HALSKY_STAGE_ALLOW_WRITES=1` for that run.
5. Rotate credentials after maintainer access changes, suspected disclosure, or the agreed lifetime. Revoke the old credential after confirming the replacement works.
6. Remove secrets when Stage verification is complete or paused for an extended period.

## Evidence rules

Evidence may contain timestamps, status codes, header names, anonymized identifiers, and observed behavior. It must not contain access tokens, authorization codes, client secrets, personal data, addresses, raw order payloads, or reusable organization identifiers.

Set a checklist item to `verified-stage` or `rejected-stage` only after adding an execution timestamp and a sanitized evidence reference. A failed or ambiguous run remains `pending-stage` with a note explaining the next check.

## Report template

```text
Stage verification: STAGE-NNN
Executed at (UTC):
SDK revision:
Stage contract version:
Maintainer:
Write operations enabled: yes/no

Scenario:
Expected behavior:
Observed behavior:
Sanitized HTTP metadata:
Result: verified-stage/rejected-stage/pending-stage
Affected phases or release gates:
Follow-up:

Redaction check:
- no tokens, codes, or secrets
- no PII or raw business payloads
- no reusable organization or resource identifiers
```
