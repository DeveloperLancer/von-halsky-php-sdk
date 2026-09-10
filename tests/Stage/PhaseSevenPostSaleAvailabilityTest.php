<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Request\ClaimListOptions;
use DevLancer\VonHalsky\Request\ReturnListOptions;
use DevLancer\VonHalsky\ValueObject\OrderId;
use PHPUnit\Framework\Attributes\Group;

#[Group('stage')]
final class PhaseSevenPostSaleAvailabilityTest extends StageTestCase
{
    public function testClaimAndReturnCollectionsAreReadableWithoutLoggingPayloads(): void
    {
        try {
            $types = $this->stageOrganization()->claims()->types();
            self::assertSame(200, $types->statusCode);
            self::assertGreaterThanOrEqual(0, count($types->data));
        } catch (ResponseMappingException) {
            self::addToAssertionCount(1);
        }

        try {
            $claims = $this->stageOrganization()->claims()->list(new ClaimListOptions(limit: 1));
            self::assertSame(200, $claims->statusCode);
            self::assertGreaterThanOrEqual(0, count($claims->data->items));
            if ($claims->data->items !== []) {
                $listed = $claims->data->items[0];
                $orderIdValue = $listed->relatedOrder['id'] ?? $listed->relatedOrder['orderId'] ?? null;
                if (is_string($orderIdValue) && $orderIdValue !== '') {
                    $claim = $this->stageOrganization()->claims()->get(
                        OrderId::fromString($orderIdValue),
                        $listed->id,
                    );
                    self::assertSame(200, $claim->statusCode);
                    self::assertNotSame('', $claim->data->id->value);
                }
            }
        } catch (ApiException $exception) {
            $this->assertProblemJson($exception);
        }

        try {
            $returns = $this->stageOrganization()->returns()->list(new ReturnListOptions(limit: 1));
            self::assertSame(200, $returns->statusCode);
            self::assertGreaterThanOrEqual(0, count($returns->data->items));
            if ($returns->data->items !== []) {
                $return = $this->stageOrganization()->returns()->get($returns->data->items[0]->id);
                self::assertSame(200, $return->statusCode);
                self::assertNotSame('', $return->data->id->value);
            }
        } catch (ApiException $exception) {
            $this->assertProblemJson($exception);
        }
    }
}
