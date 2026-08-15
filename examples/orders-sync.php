<?php

declare(strict_types=1);

use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;
use DevLancer\VonHalsky\VonHalskyClient;

require dirname(__DIR__) . '/vendor/autoload.php';

$token = getenv('VON_HALSKY_STAGE_ACCESS_TOKEN');
$organizationId = getenv('VON_HALSKY_STAGE_ORGANIZATION_ID');
$updatedAtGte = getenv('VON_HALSKY_ORDERS_UPDATED_AT_GTE');
if (!is_string($token) || !is_string($organizationId) || !is_string($updatedAtGte)) {
    throw new RuntimeException('Configure the Stage token, organization, and UTC synchronization watermark first.');
}

$client = VonHalskyClient::create(
    new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('+30 minutes'))),
    Environment::stage(),
);
$orders = $client->forOrganization(OrganizationId::fromString($organizationId))->orders();
$page = $orders->list(new OrderListOptions(
    paymentStatuses: ['PAID', 'NOT_PAID'],
    updatedAtGte: UtcDateTime::fromString($updatedAtGte),
    sort: ['updatedAt'],
));

// Deliberately print only opaque IDs and statuses, never customer or delivery payloads.
foreach ($page->data->items as $order) {
    printf("Order %s has status %s.\n", $order->id->value, $order->status->value);
}
