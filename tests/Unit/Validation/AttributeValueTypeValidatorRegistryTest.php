<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidatorInterface;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;
use PHPUnit\Framework\TestCase;

final class AttributeValueTypeValidatorRegistryTest extends TestCase
{
    public function testRegistersApplicationDefinedAttributeType(): void
    {
        $registry = AttributeValueTypeValidatorRegistry::withDefaults([
            new class implements AttributeValueTypeValidatorInterface {
                public function type(): string
                {
                    return 'APPLICATION_CODE';
                }

                public function isValid(string $value): bool
                {
                    return preg_match('/\AAPP-\d+\z/D', $value) === 1;
                }
            },
        ]);
        $type = AttributeType::fromString('APPLICATION_CODE');

        self::assertTrue($registry->supports($type));
        self::assertTrue($registry->isValid($type, 'APP-42'));
        self::assertFalse($registry->isValid($type, 'invalid'));
    }

    public function testAddsAndRemovesApplicationDefinedAttributeType(): void
    {
        $type = AttributeType::fromString('APPLICATION_CODE');
        $registry = new AttributeValueTypeValidatorRegistry([]);
        $validator = new class implements AttributeValueTypeValidatorInterface {
            public function type(): string
            {
                return 'APPLICATION_CODE';
            }

            public function isValid(string $value): bool
            {
                return $value === 'accepted';
            }
        };

        self::assertSame($registry, $registry->add($validator));
        self::assertTrue($registry->supports($type));
        self::assertFalse($registry->isValid($type, 'invalid'));
        self::assertSame($registry, $registry->remove('APPLICATION_CODE'));
        self::assertFalse($registry->supports($type));
    }

    public function testRejectsValidatorCollectionContainingOtherValues(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new AttributeValueTypeValidatorRegistry([new \stdClass()]);
    }

    public function testRejectsDuplicateValidatorType(): void
    {
        $registry = new AttributeValueTypeValidatorRegistry([]);
        $validator = new class implements AttributeValueTypeValidatorInterface {
            public function type(): string
            {
                return 'APPLICATION_CODE';
            }

            public function isValid(string $value): bool
            {
                return true;
            }
        };
        $registry->add($validator);

        $this->expectException(\InvalidArgumentException::class);
        $registry->add($validator);
    }

    public function testReplacesBuiltInValidatorAfterRemoval(): void
    {
        $registry = AttributeValueTypeValidatorRegistry::withDefaults()
            ->remove(AttributeType::NUMERIC)
            ->add(new class implements AttributeValueTypeValidatorInterface {
                public function type(): string
                {
                    return AttributeType::NUMERIC;
                }

                public function isValid(string $value): bool
                {
                    return $value === 'application-specific';
                }
            });

        $type = AttributeType::fromString(AttributeType::NUMERIC);
        self::assertTrue($registry->isValid($type, 'application-specific'));
        self::assertFalse($registry->isValid($type, '42'));
    }
}
