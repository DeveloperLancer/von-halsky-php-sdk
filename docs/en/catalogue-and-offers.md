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

### Create and validate an attribute

Do not construct `AttributeDefinition` manually. Fetch the definitions for the selected category and use the chosen definition ID as the `AttributeValue` ID. The following example creates one attribute value, places it in a product, and runs the recommended validation of the complete `ProductProposal`:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Validation\CategoryProductValidator;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;

/** @var \DevLancer\VonHalsky\VonHalskyClient $client */
$categoryId = new CategoryId('leaf-category-id');
$definitions = $client->categories()->attributes(
    $categoryId,
    ResponseLanguage::ENGLISH,
)->data;

$attributeId = 'attribute-id-returned-by-api';
$definition = null;
foreach ($definitions as $candidate) {
    if ($candidate->id === $attributeId) {
        $definition = $candidate;
        break;
    }
}

if ($definition === null) {
    throw new LogicException('The attribute does not belong to the selected category.');
}

$attribute = new AttributeValue(
    id: $definition->id,
    values: ['123'],
    language: $definition->language,
);

$proposal = new ProductProposal(
    name: 'Example product',
    description: 'This example product description is longer than one hundred characters, so it meets the local offer-form requirement.',
    brand: 'Example',
    categoryId: $categoryId,
    ean: new Ean('5901234123457'),
    attributes: [$attribute],
);

$validator = new CategoryProductValidator($categoryId, $definitions);
$validation = $validator->validate($proposal);

foreach ($validation->issues as $issue) {
    // Use $issue->level, $issue->fieldPath, and $issue->message.
}
```

`values` is always a list of strings, including when an attribute has one value. The official common `AttributeValueItem` schema permits an empty string, limits every item to 1024 characters, and does not set `minItems`, so `[]` is structurally valid. The SDK stores the current 1024 limit independently in every built-in type validator so that a future change to one type does not alter the others automatically. The definition determines the permitted item count:

| `expectedValue` | Permitted `values` item count |
| --- | --- |
| `NULL_OR_ONE` | 0 or 1 |
| `ONE` | exactly 1 |
| `AT_LEAST_ONE` | at least 1 |
| `ANY` | 0, 1, or many |

Values must also match the definition type. The example `'123'` is a valid candidate for `NUMERIC`, but it might not be valid for the definition selected in a real category. For `DICTIONARY`, pass the exact active option `value` returned in `$definition->dictionary`, not its ID. An empty `UpsertAttribute` value list is serialized according to the contract, but use `RemoveAttribute` when the intent is to remove an attribute unambiguously.

When only one value's format needs to be checked, create the context explicitly and call the type registry. The indexes must point to the attribute's and value's real positions in the product; both are `0` in the preceding example:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;

/** @var \DevLancer\VonHalsky\ValueObject\CategoryId $categoryId */
/** @var \DevLancer\VonHalsky\Model\Category\AttributeDefinition $definition */
/** @var \DevLancer\VonHalsky\Model\Offer\AttributeValue $attribute */
$context = new AttributeValueValidationContext(
    categoryId: $categoryId,
    definition: $definition,
    attribute: $attribute,
    attributeIndex: 0,
    valueIndex: 0,
);

$registry = AttributeValueTypeValidatorRegistry::withDefaults();
$typeValidation = $registry->validate($context);

foreach ($typeValidation->errors() as $error) {
    // The path is in $context->fieldPath and the description in $error->message.
}
foreach ($typeValidation->warnings() as $warning) {
    // Warnings do not make $typeValidation->isValid() return false.
}
```

Calling the registry directly runs every rule of the selected type validator, including its independent length limit. It does not check product completeness, attribute cardinality, required attributes, or dictionary membership. Use `CategoryProductValidator::validate()` before creating or updating an offer.

The validator checks category identity, required attributes, cardinality, duplicate or unknown attribute IDs, active dictionary values, and known value types. Every built-in type currently owns an independent 1024-character limit. `NUMERIC` accepts unsigned non-negative integers, `NUMERIC_FLOAT` unsigned non-negative dot-decimal values, `DATE` ISO `YYYY-MM-DD`, and `URL` absolute HTTP or HTTPS URLs. Dictionary inputs use the localized option `value` returned by the API, not the option ID. Unknown future definition types produce warnings, while a missing validator for an API-defined type is an error. Local validation does not replace the server's current business rules and is never invoked automatically by offer creation.

An application can register its own attribute type. The validator receives the category, definition, complete attribute, current value, indexes, and field path. It returns a list of errors and warnings that `CategoryProductValidator` adds to the product result. A custom type owns its limit. The `ValidatesAttributeValueLength` trait provides shared mechanics without imposing one shared limit value:

```php
<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationIssue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationResult;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidatorInterface;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeType\ValidatesAttributeValueLength;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;
use DevLancer\VonHalsky\Validation\CategoryProductValidator;

final class ApplicationCodeValidator implements AttributeValueTypeValidatorInterface
{
    use ValidatesAttributeValueLength;

    private const MAX_LENGTH = 64;

    public function type(): string
    {
        return 'APPLICATION_CODE';
    }

    public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
    {
        $issues = [];
        $lengthIssue = $this->maximumLengthIssue($context, self::MAX_LENGTH, $this->type());
        if ($lengthIssue !== null) {
            $issues[] = $lengthIssue;
        }
        if (preg_match('/\AAPP-\d+\z/D', $context->value) !== 1) {
            $issues[] = new AttributeValueTypeValidationIssue(
                'application_code_invalid',
                AttributeValueTypeValidationIssue::ERROR,
                'Application code must use the APP-123 format.',
            );
        }

        return new AttributeValueTypeValidationResult($issues);
    }
}

$registry = AttributeValueTypeValidatorRegistry::withDefaults([
    new ApplicationCodeValidator(),
]);
$validator = new CategoryProductValidator($proposal->categoryId, $definitions, $registry);
```

Calling the registry directly for an unregistered type throws `LogicException`. To replace a built-in rule, remove it and add the application validator through `remove()->add()`.

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

## Product description formatting

The product description can be formatted with HTML. The SDK sends `ProductProposal::$description` without converting or filtering markup; it only limits the complete text to `100–100000` characters.

| Effect | HTML |
| --- | --- |
| Bold | `<strong>text</strong>` |
| Italic | `<em>text</em>` |
| Underline | `<u>text</u>` |
| Bulleted list | `<ul><li>item</li></ul>` |
| Numbered list | `<ol><li>item</li></ol>` |

The API remains responsible for validation and any HTML sanitization, so do not assume arbitrary tags are supported.

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
