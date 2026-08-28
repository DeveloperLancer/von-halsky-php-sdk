<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;

require dirname(__DIR__) . '/vendor/autoload.php';

$token = getenv('VON_HALSKY_STAGE_ACCESS_TOKEN');
$organizationId = getenv('VON_HALSKY_STAGE_ORGANIZATION_ID');
$categoryId = getenv('VON_HALSKY_STAGE_LEAF_CATEGORY_ID');
$ean = getenv('VON_HALSKY_STAGE_PRODUCT_EAN');
if (!is_string($token) || !is_string($organizationId) || !is_string($categoryId) || !is_string($ean)) {
    throw new RuntimeException('Configure the Stage token, organization, leaf category, and product EAN first.');
}
if (getenv('VON_HALSKY_STAGE_ALLOW_WRITES') !== '1') {
    throw new RuntimeException('Set VON_HALSKY_STAGE_ALLOW_WRITES=1 to acknowledge that this example creates an offer.');
}

$client = VonHalskyClient::create(
    new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('+30 minutes'))),
    Environment::stage(),
);
$offers = $client->forOrganization(OrganizationId::fromString($organizationId))->offers();
$accepted = $offers->create(new CreateOfferRequest(
    new ProductProposal(
        'SDK example product',
        'This synthetic Stage offer exists only to demonstrate the SDK and is not intended for sale or order fulfilment.',
        'SDK',
        CategoryId::fromString($categoryId),
        new Ean($ean),
    ),
    new Stock(1),
    new Price(Money::fromDecimal('9.99'), '23%'),
    GpsrInfo::notRequired(),
    'sdk-example-' . bin2hex(random_bytes(8)),
    1,
    [new OfferImage('sdk-example.png', 'https://placehold.co/1200x1200/png?text=SDK+example', 1)],
));

$command = $offers->command($accepted->data->commandId)->data;
printf("Command %s has status %s. HTTP 201 did not mean that the offer was ready.\n", $command->commandId->value, $command->status->value);
