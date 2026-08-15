# Examples

Examples use placeholder environment variables and never contain reusable credentials. They are syntax-checked by the documentation quality gate and statically analyzed with the rest of the project.

## OAuth2

- [`authorization-code-pkce.php`](./authorization-code-pkce.php) — create a PKCE authorization request and identify the values that must stay in the server-side session.
- [`client-credentials.php`](./client-credentials.php) — explicitly request a merchant Client Credentials token without printing the token value.

See the [OAuth2 and token guide](../docs/en/authentication.md) for callback validation, persistent storage, shared locking, and refresh rotation.

## Organizations and categories

- [`organizations-and-categories.php`](./organizations-and-categories.php) — select an organization after OAuth and traverse a five-level read-only category tree on Stage.

See the [catalogue and offers guide](../docs/en/catalogue-and-offers.md) for category traversal, leaf validation, attribute definitions, and response metadata.

## Offers and attachments

- [`offers-and-attachments.php`](./offers-and-attachments.php) — create a Stage offer with explicit write opt-in and inspect its asynchronous command.

See the [catalogue and offers guide](../docs/en/catalogue-and-offers.md) for product/GPSR modeling, updates, events, and stream ownership.

## Orders and post-sale

- [`orders-sync.php`](./orders-sync.php) — synchronize paid and unpaid Stage orders from a UTC watermark without printing customer data.

See the [orders and post-sale guide](../docs/en/orders-and-post-sale.md) for privacy, ShipX boundaries, returns, refunds, claims, and deprecated migration.

## Reliability

- [`reliability.php`](./reliability.php) — explicitly enable bounded GET retry and fetch one newest-first event page from an application-provided event ID.

See the [reliability guide](../docs/en/reliability.md) for non-blocking command polling, retention recovery, rate-limit metadata, and application responsibilities.
