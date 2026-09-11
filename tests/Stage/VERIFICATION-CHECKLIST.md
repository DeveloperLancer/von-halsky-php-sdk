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
| Claims | `types()` dictionary envelope | confirmed; Stage returns a JSON array `[{id, description, code}]` while OpenAPI 1.6.9 documents `{data:[{id, description}]}`. Platform drift, not an SDK contract invention. SDK accepts both; `code` is retained in `additionalData`. |

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
| SKU length | Local SDK code and unit test enforce 100 characters | locally confirmed |
| Product name | 7 and 150 characters accepted on Stage after PATCH and `get()`; 6 and 151 rejected locally | confirmed |
| Product brand | 1 and 100 characters accepted on Stage after PATCH and `get()`; 101 rejected locally | confirmed |
| Product model and superModel | 100 characters accepted on Stage after PATCH and `get()`; 101 rejected locally | confirmed |
| EAN format | Non-digits and 15-digit values rejected locally; repeating the catalogue EAN via PATCH was accepted | locally confirmed |
| taxRateInfo | Empty value rejected locally; Stage create with `NOT_A_TAX_RATE` reached command `SUCCESS` | confirmed |
| PATCH `null` for required offer and product members | Rejected locally before network I/O | locally confirmed |
| PATCH first assignment and repeated assignment of external ID and EAN | First `externalId` assignment visible in `get()`; a second distinct `externalId` either persisted or was rejected by the API; repeating the current EAN was accepted | confirmed |
| Product PATCH behavior for `PENDING` and `PUBLISHED` offers | Product name PATCH was visible on the first readable status after create; a further PATCH was visible after `PUBLISHED` when that status appeared before timeout | confirmed |

## GPSR fields

| Field or rule | Result | Status |
| --- | --- | --- |
| Manufacturer name: 500 / 501 characters | 500 accepted, 501 rejected | confirmed |
| Safety information: 100000 / 100001 characters | 100000 accepted, 100001 rejected | confirmed |
| Batch number: 500 / 501 characters | 500 accepted, 501 rejected | confirmed |
| Unstructured address: 300 / 301 characters | 300 accepted, 301 rejected | confirmed |
| Phone number | `+481234567890123` accepted; value without `+` rejected | confirmed |
| Manuals: 20 / 21 | 20 accepted, 21 rejected | confirmed |
| Structured address, manufacturer email, responsible person and CE marking | Typed structured address, manufacturer email, responsible person, and `ceMarking` true/false persisted through create, PATCH, and `get()` | confirmed |

## Category attribute value types

| Type | Result | Status |
| --- | --- | --- |
| `TEXT_VALUE` | Create persists `lang=pl_PL`. `updateAttributes` upsert of a new TEXT field reaches command `SUCCESS` with empty errors, but `get()` usually still omits it. Stage offer `lang` is `pl_PL`, not the SDK `ResponseLanguage` value `pl`. | confirmed on create; inconclusive on PATCH |
| `NUMERIC` | `updateAttributes` `SUCCESS` and the value is visible in `get()` (`lang` is null). Invalid `10.5` does not replace the accepted integer. | confirmed |
| `NUMERIC_FLOAT` | `updateAttributes` `SUCCESS` and the value is visible in `get()`. Invalid `abc` does not replace the accepted float. | confirmed |
| `LONG_TEXT_VALUE` | Absent from the entire Stage tree in both `pl` and `en` (9155 categories, 8076 leaves; identical type counts). Only `TEXT_VALUE`, `NUMERIC`, and `NUMERIC_FLOAT` appear. No write probe is possible on Stage. | pending |
| `DICTIONARY` | Absent from the entire Stage tree in both `pl` and `en`. No write probe is possible on Stage. | pending |
| `DICTIONARY`: request value representation | Blocked: no Stage leaf exposes `DICTIONARY`. | pending |
| `DICTIONARY`: allowed option membership | Blocked: no Stage leaf exposes `DICTIONARY`. | pending |
| `DICTIONARY`: inactive option | Blocked: no Stage leaf exposes `DICTIONARY`. | pending |
| `DATE` | Absent from the entire Stage tree in both `pl` and `en`. No write probe is possible on Stage. | pending |
| `URL` | Absent from the entire Stage tree in both `pl` and `en`. No write probe is possible on Stage. | pending |
| Attribute cardinality, multilingual values and per-category required attributes | Configured category now exposes 13 attributes, all `NULL_OR_ONE` (formerly required text fields are optional). Multilingual write probes were not run | pending |
| HTTP 429 / `X-RateLimit-*` | Sequential attribute GETs for the full tree in `pl` and `en` (9155 unique categories each) plus earlier bursts all returned HTTP 200. No `X-RateLimit-*` or `Retry-After` on success. STAGE-005 remains unconfirmed. | inconclusive |

## Update protocol for this checklist

1. Reuse a dedicated, published Stage offer when a field can be patched safely; create a synthetic offer only when required by the API.
2. Send one boundary or format probe at a time and retain its `commandId`.
3. Poll until `SUCCESS` or `FAILURE`, then poll `get()` until the expected value is visible.
4. Restore the original value and verify restoration before recording the result.
5. Record the exact accepted/rejected boundary, final status, and whether the result was observed in the read model. Do not log secrets, customer data, payment data, addresses, or complete order models.
