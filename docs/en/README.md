# Von Halsky PHP SDK documentation

Use this documentation as a path through the SDK, not as a copy of the upstream HTTP API. It describes the PHP types and behavior implemented in this repository. Every resource method also has a dedicated page in the [operation reference](./reference/README.md).

Read this documentation in [Polish](../pl/README.md).

The guides are written for PHP developers familiar with Composer and HTTP APIs. Start with a workflow guide when designing an integration; use the reference when you already know which resource method you need.

## Choose a path

### First integration

Install the package, create a client with an existing token, list organizations, and select an immutable organization context.

1. [Installation and first client](./installation.md)
2. [Client, environments, and organization contexts](./client-and-environments.md)
3. [Responses, pagination, validation, and errors](./responses-and-errors.md)

### OAuth and tokens

Choose the appropriate grant, complete the authorization-code callback safely, and implement durable token storage and refresh coordination.

1. [OAuth 2.0 and token lifecycle](./authentication.md)
2. [Client, environments, and organization contexts](./client-and-environments.md)

### Catalogue and offers

Discover categories and attributes, then create, update, observe, and enrich offers.

1. [Catalogue and offers](./catalogue-and-offers.md)
2. [Offer reference](./reference/offers/README.md)
3. [Attachment reference](./reference/attachments/README.md)

### Orders and post-sale

Synchronize orders, process asynchronous commands, and work with returns, refunds, and claims without leaking buyer data.

1. [Orders and post-sale](./orders-and-post-sale.md)
2. [Order reference](./reference/orders/README.md)
3. [Returns reference](./reference/returns/README.md)
4. [Claims reference](./reference/claims/README.md)

### Production reliability

Set transport boundaries, use the explicitly bounded GET retry only once, persist cursors, and recover from stale event checkpoints.

1. [Reliability and application boundaries](./reliability.md)
2. [Responses, pagination, validation, and errors](./responses-and-errors.md)
3. [Production readiness checklist](./production-checklist.md)

## Guides

- [Installation and first client](./installation.md)
- [Client, environments, and organization contexts](./client-and-environments.md)
- [OAuth 2.0 and token lifecycle](./authentication.md)
- [Catalogue and offers](./catalogue-and-offers.md)
- [Orders and post-sale](./orders-and-post-sale.md)
- [Responses, pagination, validation, and errors](./responses-and-errors.md)
- [Reliability and application boundaries](./reliability.md)
- [Production readiness checklist](./production-checklist.md)
- [Compatibility](./compatibility.md)

## Reference and examples

- [All resource operations](./reference/README.md)
- [Runnable examples](../../examples/README.md)
- [Generated PHP API reference](./api-reference.md)

## Keeping docs accurate

Update the English and Polish guides plus the relevant operation pages in the same change as a public SDK behavior. `composer docs-check` validates both language trees, local links, reference coverage, and PHP syntax; examples must be credential-free and safe to copy.
