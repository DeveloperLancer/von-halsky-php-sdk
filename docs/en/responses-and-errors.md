# Responses, pagination, validation, and errors

Every successful resource call returns `ApiResponse<T>`. The domain result is in `data`; transport metadata remains next to it instead of being stored in mutable client state.

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\Resource\OrganizationsResource $organizations */
$response = $organizations->list();

$items = $response->data;
$status = $response->statusCode;
$correlationId = $response->correlationId;
```

The response fields have distinct purposes:

| Field | Meaning |
| --- | --- |
| `data` | The operation-specific model documented as `T`. |
| `statusCode` | The successful HTTP status code. |
| `headers` | An immutable allowlist of safe response headers, not every upstream header. |
| `rateLimit` | Parsed limit data when at least one relevant header exists. |
| `correlationId` | `X-Correlation-ID`, falling back to `X-Request-ID`. |

`RateLimit` can contain `limit`, `remaining`, UTC `resetAt`, UTC `retryAt`, and numeric `retryAfterSeconds`. Any field can be `null`; malformed or absent headers are not guessed. Use these values as signals for application-wide throttling, not as a promise that a later request will succeed.

## Value objects and local validation

Public identifiers are distinct immutable types such as `OrganizationId`, `OfferId`, `OrderId`, and `CommandId`. Keeping the type returned by one call prevents accidentally passing an order ID where an offer ID is expected. Monetary values use decimal strings, never binary floats:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\ValueObject\Money;

$price = Money::fromDecimal('49.90'); // Normalized to 49.90 PLN.
```

`Money` accepts `0.01` through `999999.99` with at most two fractional digits and defaults to PLN. Request DTOs validate documented constraints before network I/O and raise `InvalidRequestException` with a field path. Local validation improves feedback, but it does not replace current server-side business validation.

For JSON Merge Patch, `OptionalValue::undefined()`, `OptionalValue::null()`, and `OptionalValue::of($value)` mean omit the field, send JSON `null`, and send a concrete value respectively. This distinction prevents an omitted field from being cleared accidentally.

Response enums derived from `ExtensibleEnum` preserve unknown server values for forward compatibility. Compare their `value` and provide an unknown-state fallback in business logic. Raw string filters are only locally checked where the SDK has a confirmed list of allowed values; the server can still reject a value that changed upstream.

## Pagination

List-like calls return `ApiResponse<PageResult<T>>`. The SDK deliberately fetches one page. `items` contains the typed records, `page` contains `offset`, `limit`, and `total`, while both `PageResult` and `Page` can retain unknown metadata in `additionalData`.

```php
<?php

declare(strict_types=1);

/** @var \DevLancer\VonHalsky\Pagination\PageResult $page */
foreach ($page->items as $item) {
    // Persist each item idempotently.
}

$processedThrough = $page->page->offset + count($page->items);
$hasMore = $page->items !== [] && $processedThrough < $page->page->total;
```

Do not loop only while `count($items) === $requestedLimit`: the server-reported limit can differ, an empty page must stop the loop, and concurrent writes can move an offset-based data set. Persist a page before advancing a checkpoint, deduplicate by stable IDs, and periodically reconcile from an authoritative list. Event feeds are newest-first retention-bound feeds, not an unlimited history or a forward cursor.

## Exception model

Failures are separated by layer so an application can make different recovery decisions:

| Layer | Exception | Typical action |
| --- | --- | --- |
| Request construction | `InvalidRequestException` | Fix input; do not retry unchanged data. |
| API HTTP 400/401/403/404/409/422/429/5xx | Typed `ApiException` subclass | Inspect status, operation, structured details, and correlation ID. |
| Other non-2xx API response | `ApiException` | Treat according to status and operation semantics. |
| PSR-18 network failure | `NetworkTransportException` | Retry only when the operation is safe and within policy. |
| Invalid PSR-18 request | `InvalidTransportRequestException` | Fix transport configuration or request construction. |
| Other client transport failure | `TransportException` | Diagnose the injected transport. |
| Incompatible success payload | `ResponseMappingException` | Preserve correlation metadata and investigate a contract mismatch. |
| OAuth endpoint or token state | `AuthenticationFlowException` | Reauthorize or repair token storage; messages are redacted. |

The typed HTTP subclasses are `BadRequestException`, `AuthenticationException`, `AuthorizationException`, `NotFoundException`, `ConflictException`, `UnprocessableEntityException`, `RateLimitException`, and `ServerException`.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\RateLimitException;

try {
    $response = $shop->offers()->get($offerId);
} catch (InvalidRequestException $error) {
    // Reject or correct local input.
} catch (RateLimitException $error) {
    // Schedule a later read using $error->rateLimit; do not busy-loop.
} catch (ApiException $error) {
    // Log only approved fields such as statusCode, operationId, and correlationId.
}
```

`ApiException` exposes `statusCode`, `errorCode`, structured `details`, safe headers, optional rate-limit data, `correlationId`, and the SDK `operationId`. If an error body is invalid, the SDK may retain a redacted excerpt limited to 256 bytes for diagnosis. Treat even that excerpt as potentially sensitive and never log it by default. Authorization headers and complete response bodies are never retained.
