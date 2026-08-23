# Orders and post-sale

Orders, returns, and claims belong to an organization context. The SDK performs individual HTTP calls; it does not synchronize an application database, create ShipX shipments, or make customer-service and financial decisions.

## Synchronize orders without losing changes

`orders()->list()` returns one offset-based page. Use a UTC update watermark to bound a synchronization run, persist each page before moving progress, and upsert by stable order ID. Do not advance a durable watermark merely because the HTTP call succeeded.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;

/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$page = $shop->orders()->list(new OrderListOptions(
    paymentStatuses: ['PAID', 'NOT_PAID'],
    updatedAtGte: UtcDateTime::fromString('2026-01-01T00:00:00Z'),
    sort: ['updatedAt'],
))->data;
```

A production synchronizer should persist a run boundary, last durable offset or watermark, and its own processing status in one transaction. Re-read an overlap window or run periodic full reconciliation to cover equal timestamps, concurrent updates, and crash recovery. Exact overlap size is an application decision and should be tested against Stage behavior.

Order and offer event feeds are newest-first. `untilId` asks for events older than that ID and excludes it and newer records; it is not a forward cursor. Keep both the last event ID and checkpoint time, deduplicate event IDs, and use authoritative lists after any suspected retention gap. Do not assume a fixed event-retention duration: it is not a confirmed API guarantee.

## Commands and refunds

`orders()->accept()` returns an `OrderCommand`. A successful acceptance response can represent a pending change; store its command ID and inspect `command()` or relevant events later. POST, PATCH, and DELETE operations are never automatically retried by the SDK. If a write ends with an ambiguous network failure, reconcile remote state before issuing another write.

Calling `refund($orderId)` without `RefundRequest` requests a full refund. Passing `new RefundRequest(Money::fromDecimal('10.00'))` requests that exact partial amount. The SDK validates the monetary representation but cannot decide whether the amount is commercially correct, still refundable, or already refunded.

## Privacy and integration boundaries

`OrderDetails` contains typed identity and totals, but `orderLines`, `customer`, `delivery`, `invoice`, and `paymentDetails` are forward-compatible nested arrays. The customer email is platform hashmail; delivery and invoice data can contain direct personal data. Returns and claims can also contain customer-supplied descriptions and identifiers.

Treat complete models as sensitive even if you read only one typed field:

- do not log serialized models or exception payloads;
- minimize fields copied into analytics, queues, and ShipX integrations;
- encrypt stored personal data and apply an application retention policy;
- authorize organization access before exposing records to operators;
- use synthetic data for examples and Stage testing.

The SDK does not implement ShipX shipment creation, labels, manifests, or tracking. Configure that integration separately and pass only the required delivery fields.

## Returns and claims

Returns support list, per-order list, detail, accept, and reject operations. Claims support a global type dictionary, organization list and detail operations, and reject, partial-refund, and refund actions. Known claim states in this SDK are `APPROVED`, `REJECTED`, and `RESOLUTION_IN_PROGRESS`; response models preserve an unknown future state, while list filters remain raw strings and can still be rejected by the server.

Post-sale actions are customer-visible and can be financial. Before each call, fetch current details, apply authorization and business checks in the application, record the actor and reason in an audit trail, and avoid automatic replay. See [returns](./reference/returns/README.md) and [claims](./reference/claims/README.md).
