<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Request\OrderEventsOptions;
use DevLancer\VonHalsky\Request\OrderListOptions;
use PHPUnit\Framework\Attributes\Group;

#[Group('stage')]
final class PhaseSevenReadOnlySmokeTest extends StageTestCase
{
    public function testPollsOrderEventsAndListsOrdersOnStage(): void
    {
        $orders = $this->stageOrganization()->orders();

        $events = $orders->events(new OrderEventsOptions(limit: 100));
        self::assertSame(200, $events->statusCode);
        foreach ($events->data as $event) {
            self::assertNotSame('', $event->id->value);
            self::assertNotSame('', $event->orderId->value);
        }

        $response = $orders->list(new OrderListOptions(limit: 30, sort: ['-updatedAt']));
        self::assertSame(200, $response->statusCode);
        self::assertGreaterThanOrEqual(0, $response->data->page->offset);
        self::assertGreaterThan(0, $response->data->page->limit);
        self::assertGreaterThanOrEqual(count($response->data->items), $response->data->page->total);
    }

    public function testReadsFirstAvailableOrderDetailsOnStage(): void
    {
        $orders = $this->stageOrganization()->orders();
        $page = $orders->list(new OrderListOptions(limit: 1, sort: ['-updatedAt']))->data;
        if ($page->items === []) {
            self::markTestSkipped('The Stage organization has no marketplace-created orders to inspect.');
        }

        $listedOrder = $page->items[0];
        $response = $orders->get($listedOrder->id);
        self::assertSame(200, $response->statusCode);
        self::assertSame($listedOrder->id->value, $response->data->id->value);
        self::assertSame($this->stageOrganizationId()->value, $response->data->organizationId->value);
    }

    public function testPostSaleDictionariesAndListsRemainReadableOnStage(): void
    {
        $client = $this->stageClient();
        $organization = $this->stageOrganization();

        self::assertSame(200, $client->orders()->deliveryMethods()->statusCode);
        self::assertSame(200, $client->claims()->types()->statusCode);
        self::assertSame(200, $organization->returns()->list()->statusCode);
        self::assertSame(200, $organization->claims()->list()->statusCode);
    }
}
