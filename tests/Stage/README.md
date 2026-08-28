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
    'organization_id' => '', // Required when the token cannot list organizations.
    'leaf_category_id' => '2ac6fee7-fe14-5c77-96c0-8b6ef4142768', // Dla dzieci > Artykuły szkolne > Plecaki, torby i akcesoria szkolne > Plecaki szkolne
    'product_ean' => '5901137129488', // Head HD-241 w serduszka plecak szkolny; confirmed in the Stage product catalogue.
    'offer_image_url' => 'https://placehold.co/1200x1200/png?text=Von%20Halsky%20Stage%20Test',
    'command_timeout_seconds' => 180,
    'poll_interval_milliseconds' => 1000,
];
```

The local file is ignored by Git. Never commit it, print it, or paste its contents into test output.

The suite selects the only organization available to a token that is allowed to list organizations; otherwise configure `organization_id`. `leaf_category_id` is mandatory. The distributed fixture uses Stage EAN `5901137129488` (Head HD-241) and the Stage category `Plecaki szkolne`. Before creating an offer, the test verifies that the product hint has exactly this category ID. Product identity and descriptive data come from the hint, while required attribute IDs always come from the current category definitions because Stage hints can contain stale attribute IDs. The description is extended to the Stage business minimum of 100 characters, and the offer includes one publicly reachable test image. CI may provide the equivalent `VON_HALSKY_STAGE_CLIENT_ID`, `VON_HALSKY_STAGE_CLIENT_SECRET`, `VON_HALSKY_STAGE_ORGANIZATION_ID`, `VON_HALSKY_STAGE_LEAF_CATEGORY_ID`, `VON_HALSKY_STAGE_PRODUCT_EAN`, and `VON_HALSKY_STAGE_OFFER_IMAGE_URL` environment variables. Environment variables override local values. The polling settings can be overridden with `VON_HALSKY_STAGE_COMMAND_TIMEOUT_SECONDS` and `VON_HALSKY_STAGE_POLL_INTERVAL_MILLISECONDS`.

## Run

```shell
composer test-stage
```

The offer test obtains a token through Client Credentials, creates a uniquely correlated offer, reads and updates it, and closes it in cleanup. The order tests poll events and list orders. An individual order read is skipped when the Stage marketplace has not created any orders for the configured organization.
