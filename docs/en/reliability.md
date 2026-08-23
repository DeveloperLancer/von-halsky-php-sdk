# Reliability and application boundaries

The SDK makes one typed API call at a time. It does not run workers, persist checkpoints, write logs, coordinate distributed rate limits, or generate tracing IDs. Make those responsibilities explicit in the surrounding application.

## Enable one bounded GET retry layer

Retry is disabled by default. Enable the SDK decorator only if the injected HTTP client, proxy, service mesh, and application middleware do not already retry. Stacked retry layers multiply requests and make the elapsed-time budget misleading.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\Reliability\RetryPolicy;

$http = SymfonyHttpClientFactory::create()->withRetry(new RetryPolicy(
    maxAttempts: 2,
    baseDelaySeconds: 0.1,
    maximumDelaySeconds: 0.5,
    maximumElapsedSeconds: 1.0,
));
```

`maxAttempts` includes the first request, so `2` permits at most one replay. Only `GET` is eligible, and only for PSR-18 network errors or HTTP `429`, `502`, `503`, and `504`. HTTP 429 honors a parseable `Retry-After`; other eligible statuses use exponential full-jitter backoff. A retry is skipped when its delay would exceed `maximumElapsedSeconds`.

`HttpClientDependencies::withRetry()` rejects enabling the SDK layer twice. When constructing dependencies around an external client that already retries, set `performsRetries: true` as a declaration for application wiring and do not call `withRetry()`.

The SDK never automatically replays POST, PATCH, or DELETE. For state changes, store intent before sending, attach an application audit or idempotency key where the upstream contract supports one, and reconcile remote state after an ambiguous transport failure.

## Process asynchronous commands outside HTTP requests

HTTP 201 or 202 can mean an asynchronous command was accepted, not completed. Store the command ID, organization ID, operation type, acceptance time, and your business correlation ID. Return control to the caller, then let a queue worker or scheduler make bounded `command()` checks or consume events.

Use a terminal-state table in the application rather than treating every unfamiliar state as failure. Apply a deadline and move overdue commands to reconciliation or operator review. The SDK intentionally does not sleep, poll, choose intervals, or guarantee how long command results remain available; any numeric retention value must first be verified against current Stage behavior.

## Recover event consumers

Event feeds are useful change hints, not the only source of truth. A robust consumer:

1. Fetches one newest-first page and processes it idempotently by event ID.
2. Persists the event ID and checkpoint time only after all derived changes are durable.
3. Schedules frequent reads without assuming `untilId` is a forward cursor.
4. Periodically compares application state with authoritative offer or order lists.
5. Stops advancing the checkpoint and performs a full reconciliation after downtime, an unknown event, or a suspected retention gap.

Do not assume a fixed command or event-retention duration. Until the API provider confirms one as a guarantee, design synchronization to reconcile state periodically with resource lists.

## Coordinate rate limits and diagnostics

`ApiResponse` and `RateLimitException` expose parsed rate-limit metadata. Coordinate throttling across all application processes that share upstream credentials; an in-process delay alone does not prevent a distributed burst. On 429, prefer scheduling work for `retryAt` over blocking a web worker.

Record `correlationId`, `operationId`, organization ID, your own request ID, attempt number, and duration. Do not record access tokens, authorization headers, full models, request bodies containing personal data, or complete exception payloads. The SDK correlation ID is received from the server; it does not replace end-to-end application tracing.

## Close streams deterministically

Attachments are intentionally not buffered. The caller owns streams passed to `upload()` and response streams returned by `download()`. Close them in `finally`, stream to bounded destinations, and enforce size and time limits outside the SDK. Keeping a response stream open can retain sockets and eventually exhaust the worker pool.

Before release, work through the [production readiness checklist](./production-checklist.md).
