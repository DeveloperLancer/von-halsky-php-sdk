# Stage API verification checklist

This checklist records observed behavior of the real Stage API. It is not an API specification and does not replace server-side validation. Update an item only after the accepted command has reached its final state and, where applicable, the resulting resource is readable through `get()`.

## Statuses

- `confirmed` — observed on Stage with a final command result and, for state changes, a subsequent read.
- `rejected` — Stage rejected the input with a final command result or a field validation error.
- `inconclusive` — the command was accepted but the Stage read model did not provide a reliable persisted result.
- `pending` — not yet exercised against Stage.
- `not applicable` — the API does not expose this operation.

## Authentication, catalogue, and offer lifecycle

| Area | Scenario | Status |
| --- | --- | --- |
| OAuth | Client Credentials token with Stage scopes | confirmed |
| Categories | Tree, leaf details, and category attributes | confirmed |
| Product hints | EAN hint and leaf category match | confirmed |
| Offers | Create, command polling, `get()`, paginated `list()` and `close()` | confirmed |
| Offers | Batch price update and read-after-write | confirmed |
| Offers | Batch stock update and read-after-write | confirmed |
| Orders | `events()` and paginated `list()` | confirmed |
| Orders | `get()` for a marketplace-created order | conditional; skipped when Stage has no orders |
| Orders | Create an order through the SDK | not applicable; orders originate in the marketplace |

## Product and offer request fields

| Field or rule | Result | Status |
| --- | --- | --- |
| Product description below 100 characters | `PRODUCT_DESCRIPTION_NOT_VALID` | rejected |
| Offer without an image | `IMAGE_NOT_FOUND` | rejected |
| Offer images: 20 | Accepted | confirmed |
| Offer images: 21 | Rejected | rejected |
| Days to ship: 60 | Accepted | confirmed |
| Days to ship: 61 | Rejected | rejected |
| Stock quantity: 999999 | Accepted | confirmed |
| Stock quantity: 1000000 | Rejected | rejected |
| Gross price: 999999.99 | Accepted | confirmed |
| Gross price: 1000000 | Rejected | rejected |
| SKU length | Conflicting local expectations: SDK code enforces 100, an existing test expects 250 | unresolved |
| Product name, brand, model, supermodel, EAN and tax-rate boundaries |  | pending |

## GPSR fields

| Field or rule | Result | Status |
| --- | --- | --- |
| Manufacturer name: 500 / 501 characters | 500 accepted, 501 rejected | confirmed |
| Safety information: 100000 / 100001 characters | 100000 accepted, 100001 rejected | confirmed |
| Batch number: 500 / 501 characters | 500 accepted, 501 rejected | confirmed |
| Unstructured address: 300 / 301 characters | 300 accepted, 301 rejected | confirmed |
| Phone number | `+481234567890123` accepted; value without `+` rejected | confirmed |
| Manuals: 20 / 21 | 20 accepted, 21 rejected | confirmed |
| Structured address, manufacturer email, responsible person and CE marking |  | pending |

## Category attribute value types

| Type | Result | Status |
| --- | --- | --- |
| `TEXT_VALUE` | Text, including a JSON number normalized to text, accepted; 1024 characters accepted; 1025 rejected | confirmed |
| `NUMERIC` | Stage command acceptance was observed for several JSON/string shapes, but read-after-write was inconsistent | inconclusive |
| `NUMERIC_FLOAT` | Stage command acceptance was observed for several JSON/string shapes, but read-after-write was inconsistent | inconclusive |
| `LONG_TEXT_VALUE` | No existing published offer category exposed the type | pending |
| `DICTIONARY` | No existing published offer category exposed the type for a write probe | pending |
| `DATE` | No existing published offer category exposed the type | pending |
| `URL` | No existing published offer category exposed the type | pending |
| Attribute cardinality, multilingual values, dictionary option ID/value semantics and per-category required attributes |  | pending |

## Update protocol for this checklist

1. Reuse a dedicated, published Stage offer when a field can be patched safely; create a synthetic offer only when required by the API.
2. Send one boundary or format probe at a time and retain its `commandId`.
3. Poll until `SUCCESS` or `FAILURE`, then poll `get()` until the expected value is visible.
4. Restore the original value and verify restoration before recording the result.
5. Record the exact accepted/rejected boundary, final status, and whether the result was observed in the read model. Do not log secrets, customer data, payment data, addresses, or complete order models.
