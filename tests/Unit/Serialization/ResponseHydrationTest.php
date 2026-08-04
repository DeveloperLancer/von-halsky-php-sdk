<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Serialization;

use DevLancer\VonHalsky\Internal\ResponseHydrator;
use DevLancer\VonHalsky\Model\ExtensibleEnum;
use PHPUnit\Framework\TestCase;

final class ResponseHydrationTest extends TestCase
{
    public function testUnknownEnumsAndFieldsRemainForwardCompatible(): void
    {
        $status = TestStatus::fromString('FUTURE_STATUS');
        $data = ['id' => '1', 'futureField' => ['nested' => true]];

        self::assertFalse($status->isKnown());
        self::assertNull($status->knownValue());
        self::assertSame(
            ['futureField' => ['nested' => true]],
            ResponseHydrator::additionalData($data, ['id']),
        );
    }
}

final class TestStatus extends ExtensibleEnum
{
    /** @return list<string> */
    protected static function knownValues(): array
    {
        return ['KNOWN'];
    }
}
