<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Serialization;

use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Model\RequestDtoInterface;
use DevLancer\VonHalsky\Serialization\RequestNormalizer;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use PHPUnit\Framework\TestCase;

final class RequestNormalizerTest extends TestCase
{
    public function testPatchPreservesAbsentNullAndValueStates(): void
    {
        $dto = new class implements RequestDtoInterface {
            /** @return array<string, mixed> */
            public function jsonSerialize(): array
            {
                return [
                    'absent' => OptionalValue::undefined(),
                    'cleared' => OptionalValue::null(),
                    'title' => OptionalValue::of('New title'),
                    'id' => OfferId::fromString('offer-1'),
                    'price' => Money::fromDecimal('10.00'),
                ];
            }
        };

        self::assertSame([
            'cleared' => null,
            'title' => 'New title',
            'id' => 'offer-1',
            'price' => 10.0,
        ], (new RequestNormalizer())->normalize($dto));
    }
}
