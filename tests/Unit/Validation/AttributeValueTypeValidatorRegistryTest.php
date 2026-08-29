<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Validation;

use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Tests\Unit\Validation\AttributeType\AttributeValueValidationContextFactory;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationIssue;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidationResult;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueTypeValidatorInterface;
use DevLancer\VonHalsky\Validation\AttributeType\AttributeValueValidationContext;
use DevLancer\VonHalsky\Validation\AttributeValueTypeValidatorRegistry;
use PHPUnit\Framework\TestCase;

final class AttributeValueTypeValidatorRegistryTest extends TestCase
{
    public function testRegistersApplicationDefinedAttributeType(): void
    {
        $registry = AttributeValueTypeValidatorRegistry::withDefaults([
            $this->applicationCodeValidator(),
        ]);
        $type = AttributeType::fromString('APPLICATION_CODE');

        self::assertTrue($registry->supports($type));
        self::assertTrue($registry->validate($this->context('APPLICATION_CODE', 'APP-42'))->isValid());
        self::assertFalse($registry->validate($this->context('APPLICATION_CODE', 'invalid'))->isValid());
    }

    public function testAddsAndRemovesApplicationDefinedAttributeType(): void
    {
        $type = AttributeType::fromString('APPLICATION_CODE');
        $registry = new AttributeValueTypeValidatorRegistry([]);

        self::assertSame($registry, $registry->add($this->applicationCodeValidator()));
        self::assertTrue($registry->supports($type));
        self::assertFalse($registry->validate($this->context('APPLICATION_CODE', 'invalid'))->isValid());
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
        $validator = $this->applicationCodeValidator();
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

                public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
                {
                    if ($context->value === 'application-specific') {
                        return AttributeValueTypeValidationResult::valid();
                    }

                    return new AttributeValueTypeValidationResult([
                        new AttributeValueTypeValidationIssue('application_numeric', AttributeValueTypeValidationIssue::ERROR, 'Application-specific numeric value is invalid.'),
                    ]);
                }
            });

        self::assertTrue($registry->validate($this->context(AttributeType::NUMERIC, 'application-specific'))->isValid());
        self::assertFalse($registry->validate($this->context(AttributeType::NUMERIC, '42'))->isValid());
    }

    public function testThrowsWhenValidatedTypeHasNoRegisteredValidator(): void
    {
        $registry = new AttributeValueTypeValidatorRegistry([]);

        $this->expectException(\LogicException::class);
        $registry->validate($this->context(AttributeType::NUMERIC, '42'));
    }

    private function applicationCodeValidator(): AttributeValueTypeValidatorInterface
    {
        return new class implements AttributeValueTypeValidatorInterface {
            public function type(): string
            {
                return 'APPLICATION_CODE';
            }

            public function validate(AttributeValueValidationContext $context): AttributeValueTypeValidationResult
            {
                if (preg_match('/\AAPP-\d+\z/D', $context->value) === 1) {
                    return AttributeValueTypeValidationResult::valid();
                }

                return new AttributeValueTypeValidationResult([
                    new AttributeValueTypeValidationIssue('application_code_invalid', AttributeValueTypeValidationIssue::ERROR, 'Application code is invalid.'),
                ]);
            }
        };
    }

    private function context(string $type, string $value): AttributeValueValidationContext
    {
        return AttributeValueValidationContextFactory::create($type, $value);
    }
}
