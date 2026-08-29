<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType;

use DevLancer\VonHalsky\Validation\AttributeType\TextValueValidator;
use PHPUnit\Framework\TestCase;

final class TextValueValidatorTest extends TestCase
{
    public function testAcceptsTextValueAtConfirmedStageLimit(): void
    {
        self::assertTrue((new TextValueValidator())->isValid(str_repeat('ą', 1024)));
    }

    public function testRejectsTextValueAboveConfirmedStageLimit(): void
    {
        self::assertFalse((new TextValueValidator())->isValid(str_repeat('ą', 1025)));
    }
}
