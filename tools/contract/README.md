# Contract tools

These dependency-free PHP 8.1 tools maintain the auditable API baseline used to build the SDK. They read only official InPost Von Halsky sources. The full OpenAPI documents are local inputs under ignored `var/contract/`; only derived metadata is committed while redistribution rights remain unconfirmed.

## Workflow

Run the commands from the repository root:

```bash
php tools/contract/extract-openapi.php https://inpsa-api-portal.inpost-group.com/gokart-api.html var/contract/prod.raw.json
php tools/contract/extract-openapi.php https://inpsa-api-portal.inpost-group.com/gokart-api-next.html var/contract/next.raw.json
php tools/contract/normalize-openapi.php var/contract/prod.raw.json var/contract/prod.json
php tools/contract/normalize-openapi.php var/contract/next.raw.json var/contract/next.json
php tools/contract/build-operation-manifest.php var/contract/prod.json resources/contract/operations.json
php tools/contract/diff-openapi.php var/contract/prod.json var/contract/next.json resources/contract/prod-next-diff.json
php tools/contract/validate-contract-data.php resources/contract
php tools/contract/check-drift.php var/contract/prod.json var/contract/next.json resources/contract/contract-lock.json var/contract/drift-report.json
```

After regeneration, update `resources/contract/contract-lock.json` with the normalized SHA-256 hashes and source versions, review every classified diff, and update validation rules only from official sources. Never add credentials, Stage evidence containing PII, or the full OpenAPI documents.

Direct HTTPS input requires PHP's HTTPS stream support (normally provided by OpenSSL). If it is unavailable in a minimal runtime, download the official Redoc page with an approved system tool and pass its local path to the extractor.

## Failure behavior

Every command exits non-zero for invalid arguments, unreadable input, malformed JSON, or an invalid OpenAPI document. The final validator additionally enforces the locked counts, phase allocation, deprecated endpoint decisions, source provenance, Stage safeguards, and absence of common credential formats.

`check-drift.php` is intended for the scheduled CI workflow. It compares freshly normalized official contracts with the committed lock and derived reports, exits non-zero when review is required, and never copies the full downloaded specifications into repository artifacts.

The [Stage verification procedure](./STAGE.md) defines credential handling, write safeguards, rotation, evidence redaction, and the report template for the deferred integration gate.
