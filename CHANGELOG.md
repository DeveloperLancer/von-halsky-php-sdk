# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project intends to follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Reproducible production and upcoming API contract baseline.
- Dependency-free PHP 8.1 contract extraction, normalization, manifest, diff, and validation tools.
- Composer library foundation with PHPUnit, PHPStan, PHP-CS-Fixer, and phpDocumentor.
- Offline contract tests, documentation checks, and GitHub Actions workflows.
- Immutable Stage, Production, and validated custom environments.
- PSR-18 request execution with RFC 3986 query, JSON, form, direct stream, and streaming multipart bodies.
- Secure default Symfony PSR-18 factory and an optional, lazily checked Guzzle factory.
- Safe mapping of PSR-18 client failures to SDK transport exceptions.
- OAuth2 Authorization Code + PKCE, Client Credentials, opaque token models, token contexts, stores, guarded refresh rotation, and authentication documentation.
- Immutable identifiers, money, measurements, addresses, UTC dates, and cursor value objects with confirmed API 1.6 validation limits.
- Tri-state PATCH values, request normalization, forward-compatible response hydration, typed API exceptions, rate-limit parsing, and bounded generic pagination.
- `VonHalskyClient`, immutable organization contexts, and typed organization and category resources covering 4 of 42 production operations.
- Category tree/detail DTOs, leaf validation, forward-compatible attribute definitions, typed request options, and generic API response metadata.
- Complete offer and offer-attachment resources covering 18 additional production operations (22 of 42 total).
- Typed product, GPSR, price, stock, batch, merge-patch, attribute-operation, command, event, hint, and attachment models.
- Stream-first multipart uploads and downloads with explicit caller ownership, plus an opt-in Stage offer lifecycle suite.
- Complete order, return, refund, and claim resources covering 18 current production operations (40 supported operations total).
- Typed order events/commands, UTC list filters, precise refund requests, post-sale actions, delivery methods v2, and recursive PII redaction.
- Phase 8 reliability primitives: explicitly enabled, short GET-only retry with jitter, elapsed-time limits, `Retry-After` support, and double-retry detection.
- One-call command and event endpoint access while leaving polling state, checkpoint age, scheduling, and persistence to the application.
- Correlation IDs and rate-limit metadata returned to the application without SDK logging, tracing, metrics, or request markers.

### Changed

- Local OAuth failures now use `AuthenticationFlowException`; `AuthenticationException` represents an HTTP 401 API response.

[Unreleased]: https://github.com/DeveloperLancer/von-halsky-php-sdk/commits/HEAD
