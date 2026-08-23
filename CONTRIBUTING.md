# Contributing

## Development setup

Use PHP 8.1 or newer and Composer 2:

```bash
composer install
composer quality
composer phpstan:max
```

`composer quality` is offline and excludes Stage. Do not make a pull request green by adding a PHPStan baseline, a broad ignored error, an order-dependent test, or a disabled security check.

Generate the supplementary phpDocumentor API output with `composer docs-build`. The Stage test group requires dedicated credentials; follow the [Stage verification guide](./tools/contract/STAGE.md).

## Change requirements

- Add tests for behavior and contract changes.
- Add `declare(strict_types=1)` to every project PHP file.
- Document every public symbol and update user documentation with public behavior.
- For a public SDK behavior, update the English and Polish guides and every relevant operation page in the same change. Run `composer docs-check` to validate documentation coverage, local links, and PHP example syntax.
- Update the changelog for public API or compatibility changes.
- Keep full official OpenAPI documents under ignored `var/contract/`.
- Never commit credentials, tokens, private keys, personal data, production payloads, or recorded authenticated traffic.
- Use only official InPost sources and sanitized Stage evidence for API behavior.

## Contract changes

Regenerate derived contract artifacts with the documented [contract workflow](./tools/contract/README.md). Review version changes, normalized hashes, every changed operation or schema, and all validation rules before committing metadata.

## Pull requests

Keep each pull request focused. Explain the behavioral change, tests, compatibility impact, documentation updates, and any remaining Stage verification. Public API, OAuth, contract generator, and CI security changes require especially careful review.
