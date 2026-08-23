# Stage integration tests

These tests call the real Von Halsky Stage environment. The normal `composer test` command excludes them. Every `composer test-stage` run creates, updates, and closes a synthetic Stage offer.

## Local configuration

Copy `stage-config.local.php.dist` to `stage-config.local.php` and fill in `client_id`, `client_secret`, and `product_ean`:

```php
<?php

declare(strict_types=1);

return [
    'client_id' => 'stage-client-id',
    'client_secret' => 'stage-client-secret',
    'organization_id' => '', // Optional: needed only for a token with multiple organizations.
    'leaf_category_id' => '', // Optional: overrides automatic compatible-category selection.
    'product_ean' => '5901234123457',
    'command_timeout_seconds' => 60,
    'poll_interval_milliseconds' => 1000,
];
```

The local file is ignored by Git. Never commit it, print it, or paste its contents into test output.

The suite chooses the only organization available to the token and a leaf category without mandatory attributes. If the token has multiple organizations or no compatible category is found, configure the optional IDs explicitly. CI may provide the equivalent `VON_HALSKY_STAGE_CLIENT_ID`, `VON_HALSKY_STAGE_CLIENT_SECRET`, `VON_HALSKY_STAGE_ORGANIZATION_ID`, `VON_HALSKY_STAGE_LEAF_CATEGORY_ID`, and `VON_HALSKY_STAGE_PRODUCT_EAN` environment variables. Environment variables override local values. The polling settings can be overridden with `VON_HALSKY_STAGE_COMMAND_TIMEOUT_SECONDS` and `VON_HALSKY_STAGE_POLL_INTERVAL_MILLISECONDS`.

## Run

```shell
composer test-stage
```

The offer test obtains a token through Client Credentials, creates a uniquely correlated offer, reads and updates it, and closes it in cleanup. The order tests poll events and list orders. An individual order read is skipped when the Stage marketplace has not created any orders for the configured organization.
