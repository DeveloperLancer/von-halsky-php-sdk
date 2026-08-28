# Catalogue, offers, and attachments

The catalogue is global; offer and attachment work is organization-scoped. A safe workflow is: select an organization, locate a leaf category, read its attributes, validate a product proposal, submit an offer command, then observe the command result outside the initiating web request.

## Discover catalogue data

`categories()->list()` returns a bounded tree, while `get()` and `attributes()` provide the detail needed to build a product. Children present in a `Category` are only the data included in that response; reading them does not trigger hidden HTTP requests.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\CategoryTreeOptions;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$categories = $client->categories()->list(
    new CategoryTreeOptions(depth: 4),
)->data;
```

Passing a hydrated `Category` to `ProductProposal` invokes `Category::requireLeaf()` and rejects a known non-leaf locally. Passing only a `CategoryId` cannot prove leaf status, so the SDK trusts the caller and the server remains authoritative. Prefer a freshly fetched category when creating a product. Unknown response enum values are retained rather than discarded; see [responses and errors](./responses-and-errors.md).

## Validate category requirements explicitly

Category-dependent validation is opt-in. `productValidator()` performs one attribute-definition request and returns a validator configured for that category. Calling `validate()` performs no HTTP requests and returns every detected error and warning instead of throwing for product-data problems:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Request\ResponseLanguage;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
/** @var \DevLancer\VonHalsky\Model\Offer\ProductProposal $proposal */
$validatorResponse = $client->categories()->productValidator(
    $proposal->categoryId,
    ResponseLanguage::ENGLISH,
);
$validation = $validatorResponse->data->validate($proposal);

if (!$validation->isValid()) {
    foreach ($validation->errors() as $error) {
        // Present $error->fieldPath and $error->message before submitting the offer.
    }
}
```

When the application already owns fresh or cached definitions, construct the same validator without another request:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Validation\CategoryProductValidator;

/** @var list<\DevLancer\VonHalsky\Model\Category\AttributeDefinition> $definitions */
/** @var \DevLancer\VonHalsky\Model\Offer\ProductProposal $proposal */
$validator = new CategoryProductValidator($proposal->categoryId, $definitions);
$validation = $validator->validate($proposal);
```

The validator checks category identity, required attributes, cardinality, duplicate or unknown attribute IDs, active dictionary values, and known value types. `NUMERIC` accepts signed integers, `NUMERIC_FLOAT` signed dot-decimal values, `DATE` ISO `YYYY-MM-DD`, and `URL` absolute HTTP or HTTPS URLs. Dictionary inputs use the localized option `value` returned by the API, not the option ID. Unknown future definition types produce warnings. Local validation does not replace the server's current business rules and is never invoked automatically by offer creation.

## Build a valid offer

The most important locally enforced offer-form rules are:

| Value | SDK rule                                                                                                        |
| --- |-----------------------------------------------------------------------------------------------------------------|
| Product name | 7–150 characters                                                                                                |
| Description | 100–100000 characters                                                                                           |
| Brand | 1–100 characters                                                                                                |
| Model and supermodel | 1–100 characters each, when supplied                                                                            |
| SKU | 1–100 characters                                                                                                |
| Offer images | 1–20 entries; filename ending in `.jpg`, `.png`, or `.webp`                                                     |
| Product identifiers | At least one of EAN or manufacturer product number                                                              |
| Product attributes | At most 120 entries                                                                                             |
| Stock | 0–999999                                                                                                        |
| Gross money amount | `0.01`–`999999.99`, at most two decimal places                                                                  |
| Tax-rate description | 1–100 characters                                                                                                |
| Days to ship | 0–60 when supplied                                                                                              |
| Batch creation | 1–500 offers                                                                                                    |
| GPSR manufacturer | Name, email, and responsible person: at most 500; unstructured address: at most 300; phone: `+` and 3–15 digits |
| GPSR information | Safety information: at most 100000; batch number: at most 500; CE marking: boolean                              |
| GPSR manuals | At most 20; each title 5–500 and URL 9–2048 characters                                                          |

`GpsrInfo::required()` additionally requires a manufacturer name, full address, valid email, and non-empty safety information. `GpsrInfo::notRequired()` serializes the explicit contract exemption; do not use it merely to bypass missing compliance data.

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Address;
use DevLancer\VonHalsky\ValueObject\CountryCode;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\Sku;

/** @var \DevLancer\VonHalsky\Model\Category\Category $leafCategory */
/** @var \DevLancer\VonHalsky\OrganizationContext $shop */
$result = $shop->offers()->create(new CreateOfferRequest(
    product: new ProductProposal(
        name: 'Example product',
        description: 'This example product description is longer than one hundred characters, so it meets the local offer-form requirement.',
        brand: 'Example',
        categoryId: $leafCategory,
        ean: new Ean('5901234123457'),
        sku: new Sku('EXAMPLE-001'),
    ),
    stock: new Stock(10),
    price: new Price(Money::fromDecimal('49.90'), '23%'),
    gpsr: GpsrInfo::required(
        'Example manufacturer',
        new Address('Example Street', 'Warsaw', '00-001', new CountryCode('PL'), '10'),
        'manufacturer@example.com',
        'Keep this product away from children.',
    ),
    daysToShip: 2,
    images: [new OfferImage('example-product.webp', 'https://example.com/example-product.webp', 1)],
));

$commandId = $result->data->commandId;
```

HTTP 201 confirms command acceptance, not offer availability. Persist the command ID and acceptance time in the same durable workflow record, then let a scheduled worker call `command()` or consume `events()`. Do not hold a web request open while polling.

## Update offers deliberately

Use batch DTOs for price and stock updates, `PatchOfferRequest` with `OptionalValue` for merge-patch semantics, and ordered `UpsertAttribute`/`RemoveAttribute` operations for attributes. A successful synchronous `patch()` and an accepted asynchronous command have different meanings; the individual [offer operation pages](./reference/offers/README.md) identify the returned type.

All create, update, close, reopen, upload, and delete calls change remote state. They are not automatically retried. Exercise them in Stage only after an explicit write opt-in, use synthetic data, and record your application command or audit ID so an ambiguous transport failure can be reconciled before another write.

## Own attachment streams

`upload()` reads a caller-owned PSR-7 stream without buffering or closing it. `download()` returns the response stream without loading the entire attachment into memory. The application must close both kinds of stream, including on an exception:

```php
<?php

declare(strict_types=1);

/** @var \Psr\Http\Message\StreamInterface $stream */
try {
    $download = $shop->attachments()->download($offerId, $attachmentId)->data;
    $stream = $download->stream;
    // Copy incrementally to an application-owned destination.
} finally {
    if (isset($stream)) {
        $stream->close();
    }
}
```

Validate filenames, MIME types, destination paths, size limits, and malware policy in the application. See [attachment operations](./reference/attachments/README.md) and [production reliability](./reliability.md).
