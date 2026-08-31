<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Request\AttachmentListOptions;
use DevLancer\VonHalsky\Request\CategoryDetailsOptions;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\Request\ClaimListOptions;
use DevLancer\VonHalsky\Request\OfferEventsOptions;
use DevLancer\VonHalsky\Request\OfferListOptions;
use DevLancer\VonHalsky\Request\OrderEventsOptions;
use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\Request\ProductHintOptions;
use DevLancer\VonHalsky\Request\ReturnListOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RequestOptionsValidationTest extends TestCase
{
    #[DataProvider('invalidStringListProvider')]
    public function testStringFilterListsRejectAssociativeAndNonStringEntries(callable $create, string $fieldPath): void
    {
        try {
            $create();
            self::fail(sprintf('Expected invalid field "%s".', $fieldPath));
        } catch (InvalidRequestException $exception) {
            self::assertSame($fieldPath, $exception->fieldPath);
        }
    }

    /** @return iterable<string, array{callable, string}> */
    public static function invalidStringListProvider(): iterable
    {
        yield 'claim states must be a list' => [static fn (): object => self::constructWithInvalidArguments(ClaimListOptions::class, ['states' => ['state' => 'APPROVED']]), 'claims.states'];
        yield 'claim sort values must be strings' => [static fn (): object => self::constructWithInvalidArguments(ClaimListOptions::class, ['sort' => [123]]), 'claims.sort[0]'];
        yield 'offer statuses must be a list' => [static fn (): object => self::constructWithInvalidArguments(OfferListOptions::class, ['statuses' => ['status' => 'ACTIVE']]), 'offers.statuses'];
        yield 'offer sort values must be strings' => [static fn (): object => self::constructWithInvalidArguments(OfferListOptions::class, ['sort' => [123]]), 'offers.sort[0]'];
        yield 'order statuses must be a list' => [static fn (): object => self::constructWithInvalidArguments(OrderListOptions::class, ['statuses' => ['status' => 'CREATED']]), 'orders.statuses'];
        yield 'order payment status values must be strings' => [static fn (): object => self::constructWithInvalidArguments(OrderListOptions::class, ['paymentStatuses' => [123]]), 'orders.paymentStatuses[0]'];
        yield 'return statuses must be a list' => [static fn (): object => self::constructWithInvalidArguments(ReturnListOptions::class, ['statuses' => ['status' => 'ACCEPTED']]), 'returns.statuses'];
        yield 'offer event type values must be strings' => [static fn (): object => self::constructWithInvalidArguments(OfferEventsOptions::class, ['types' => [123]]), 'offerEvents.types[0]'];
        yield 'order event types must be a list' => [static fn (): object => self::constructWithInvalidArguments(OrderEventsOptions::class, ['types' => ['type' => 'CREATED']]), 'orderEvents.types'];
    }

    #[DataProvider('invalidPaginationProvider')]
    public function testPaginationOptionsRejectInvalidLimitsAndOffsets(callable $create, string $fieldPath): void
    {
        try {
            $create();
            self::fail(sprintf('Expected invalid field "%s".', $fieldPath));
        } catch (InvalidRequestException $exception) {
            self::assertSame($fieldPath, $exception->fieldPath);
        }
    }

    /** @return iterable<string, array{callable, string}> */
    public static function invalidPaginationProvider(): iterable
    {
        yield 'attachment limit below minimum' => [static fn (): AttachmentListOptions => new AttachmentListOptions(-1), 'attachments.limit'];
        yield 'attachment limit above maximum' => [static fn (): AttachmentListOptions => new AttachmentListOptions(31), 'attachments.limit'];
        yield 'attachment offset below minimum' => [static fn (): AttachmentListOptions => new AttachmentListOptions(offset: -1), 'attachments.offset'];
        yield 'offer offset below minimum' => [static fn (): OfferListOptions => new OfferListOptions(offset: -1), 'offers.offset'];
        yield 'order limit above maximum' => [static fn (): OrderListOptions => new OrderListOptions(limit: 31), 'orders.limit'];
        yield 'claim offset below minimum' => [static fn (): ClaimListOptions => new ClaimListOptions(offset: -1), 'claims.offset'];
        yield 'return limit above maximum' => [static fn (): ReturnListOptions => new ReturnListOptions(limit: 31), 'returns.limit'];
        yield 'offer events limit above maximum' => [static fn (): OfferEventsOptions => new OfferEventsOptions(limit: 1001), 'offerEvents.limit'];
        yield 'order events limit below minimum' => [static fn (): OrderEventsOptions => new OrderEventsOptions(limit: -1), 'orderEvents.limit'];
    }

    public function testSortOptionsRejectUnsupportedValuesAndExcessiveOrderSorts(): void
    {
        self::assertInvalidField('offers.sort', static fn (): OfferListOptions => new OfferListOptions(sort: ['unsupported']));
        self::assertInvalidField('claims.sort', static fn (): ClaimListOptions => new ClaimListOptions(sort: ['unsupported']));
        self::assertInvalidField('orders.sort', static fn (): OrderListOptions => new OrderListOptions(sort: ['createdAt', '-createdAt', 'updatedAt', '-updatedAt']));
    }

    #[DataProvider('invalidDepthProvider')]
    public function testCategoryDepthOptionsRejectValuesOutsideContract(int $depth): void
    {
        foreach ([CategoryTreeOptions::class, CategoryDetailsOptions::class] as $class) {
            try {
                new $class($depth);
                self::fail(sprintf('Expected %s to reject depth %d.', $class, $depth));
            } catch (InvalidRequestException) {
                self::addToAssertionCount(1);
            }
        }
    }

    /** @return iterable<string, array{int}> */
    public static function invalidDepthProvider(): iterable
    {
        yield 'below minimum' => [-1];
        yield 'above maximum' => [11];
    }

    public function testProductHintRejectsEmptyNameCriterion(): void
    {
        self::assertInvalidField('offerHint.name', static fn (): ProductHintOptions => new ProductHintOptions(name: ''));
    }

    private static function assertInvalidField(string $fieldPath, callable $create): void
    {
        try {
            $create();
            self::fail(sprintf('Expected invalid field "%s".', $fieldPath));
        } catch (InvalidRequestException $exception) {
            self::assertSame($fieldPath, $exception->fieldPath);
        }
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $arguments
     */
    private static function constructWithInvalidArguments(string $class, array $arguments): object
    {
        return (new \ReflectionClass($class))->newInstanceArgs($arguments);
    }
}
