# Production readiness checklist

Use this checklist before sending production traffic. It focuses on responsibilities intentionally left outside the SDK.

## Credentials and environments

- Keep Stage and Production client IDs, secrets, tokens, organization IDs, and storage namespaces separate.
- Store complete `TokenSet` objects atomically and coordinate refresh with a shared `LockInterface` in multi-process or multi-host deployments.
- Request the smallest practical OAuth scope set; do not use `OAuthScope::all()` by default without reviewing the integration’s needs.
- Never log authorization codes, PKCE verifiers, client secrets, tokens, request bodies, or complete API response models.

## HTTP and failure handling

- Set an application-appropriate timeout and verify that redirects remain disabled.
- Enable SDK retry only if the HTTP client, proxy, service mesh, and application middleware do not already retry.
- Handle typed API exceptions separately from transport, response-mapping, and OAuth-flow exceptions.
- Treat `429` as a coordination signal: observe `RateLimit`, apply shared throttling in the application, and retain the correlation ID for support.

## State-changing and asynchronous work

- Never infer completed business state from HTTP 201 or 202; persist command IDs and acceptance times.
- Run command checks and event consumption from bounded background jobs, not from a long-running web request.
- Make reconciliation idempotent and persist pages or event checkpoints transactionally with the state they update.
- Review every POST, PATCH, and DELETE call for authorization, audit logging, and duplicate-submission protection; the SDK never retries these methods.

## Data and streams

- Apply data minimization and retention rules to order, return, and claim payloads; nested arrays can contain personal data.
- Close caller-owned upload streams and downloaded response streams in `finally` blocks.
- Store event checkpoint timestamps as well as IDs, and define a full-list reconciliation path for retention gaps.
- Monitor failures by exception type, operation ID, status code, rate-limit metadata, and correlation ID without recording sensitive payloads.

## Release verification

Run the complete offline gate and build the generated API reference from the exact revision being deployed:

```bash
composer quality
composer phpstan:max
composer docs-build
```

Run Stage tests only with dedicated credentials and the safeguards described in the repository’s [Stage verification procedure](https://github.com/DeveloperLancer/von-halsky-php-sdk/blob/main/tools/contract/STAGE.md).
