<?php

declare(strict_types=1);

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\Reliability\RetryPolicy;
use DevLancer\VonHalsky\Request\OrderEventsOptions;
use DevLancer\VonHalsky\ValueObject\EventId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;

require dirname(__DIR__) . '/vendor/autoload.php';

$token = getenv('VON_HALSKY_STAGE_ACCESS_TOKEN');
$organizationId = getenv('VON_HALSKY_STAGE_ORGANIZATION_ID');
$eventId = getenv('VON_HALSKY_EVENT_ID');
if (!is_string($token) || !is_string($organizationId) || !is_string($eventId)) {
    throw new RuntimeException('Configure the Stage token, organization, and event ID first.');
}

$http = SymfonyHttpClientFactory::create()->withRetry(new RetryPolicy(
    maxAttempts: 2,
    maximumElapsedSeconds: 1.0,
));
$client = new VonHalskyClient(
    Environment::stage(),
    new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('+30 minutes'))),
    $http,
);
$orders = $client->forOrganization(OrganizationId::fromString($organizationId))->orders();
$page = $orders->events(new OrderEventsOptions(untilId: EventId::fromString($eventId)));

foreach ($page->data as $event) {
    // Persist by event ID. The application decides whether and when to schedule another page.
}
