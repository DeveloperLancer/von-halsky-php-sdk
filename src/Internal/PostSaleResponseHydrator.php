<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Model\Claim\ClaimDetails;
use DevLancer\VonHalsky\Model\Claim\ClaimState;
use DevLancer\VonHalsky\Model\Claim\ClaimType;
use DevLancer\VonHalsky\Model\Offer\CommandStatus;
use DevLancer\VonHalsky\Model\Order\DeliveryMethod;
use DevLancer\VonHalsky\Model\Order\OrderCommand;
use DevLancer\VonHalsky\Model\Order\OrderDetails;
use DevLancer\VonHalsky\Model\Order\OrderEvent;
use DevLancer\VonHalsky\Model\Order\OrderEventType;
use DevLancer\VonHalsky\Model\Order\OrderStatus;
use DevLancer\VonHalsky\Model\Order\RefundResult;
use DevLancer\VonHalsky\Model\Order\RefundStatus;
use DevLancer\VonHalsky\Model\PostSale\ActionResult;
use DevLancer\VonHalsky\Model\ReturnOrder\ReturnDetails;
use DevLancer\VonHalsky\Model\ReturnOrder\ReturnStatus;
use DevLancer\VonHalsky\Pagination\Page;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\ValueObject\ClaimId;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\Currency;
use DevLancer\VonHalsky\ValueObject\EventId;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\ValueObject\ReturnId;

/** @internal Strict identity/status/money mapping with forward-compatible payload retention. */
final class PostSaleResponseHydrator
{
    /** @param array<string, mixed> $data */
    public static function order(array $data, string $path = '$'): OrderDetails
    {
        return new OrderDetails(
            OrderId::fromString(self::string($data, 'id', $path)),
            OrganizationId::fromString(self::string($data, 'organizationId', $path)),
            OrderStatus::fromString(self::string($data, 'status', $path)),
            self::money(self::object($data['finalPrice'] ?? null, $path . '.finalPrice'), $path . '.finalPrice'),
            self::money(self::object($data['basePrice'] ?? null, $path . '.basePrice'), $path . '.basePrice'),
            self::objectList($data, 'orderLines', $path),
            self::optionalObject($data, 'customer', $path),
            self::object($data['delivery'] ?? null, $path . '.delivery'),
            isset($data['invoice']) ? self::object($data['invoice'], $path . '.invoice') : null,
            self::object($data['paymentDetails'] ?? null, $path . '.paymentDetails'),
            self::optionalDate($data, 'createdAt', $path),
            self::optionalDate($data, 'updatedAt', $path),
            ResponseHydrator::additionalData($data, ['id', 'organizationId', 'status', 'finalPrice', 'basePrice', 'orderLines', 'customer', 'delivery', 'invoice', 'paymentDetails', 'createdAt', 'updatedAt']),
        );
    }

    /** @param array<string, mixed> $data
     *  @return PageResult<OrderDetails>
     */
    public static function orders(array $data): PageResult
    {
        $items = [];
        foreach (self::list($data, 'data', '$') as $index => $item) {
            $items[] = self::order(self::object($item, '$.data[' . $index . ']'), '$.data[' . $index . ']');
        }

        return new PageResult($items, self::page(self::object($data['page'] ?? null, '$.page')));
    }

    /** @param array<string, mixed> $data */
    public static function command(array $data): OrderCommand
    {
        return new OrderCommand(
            CommandId::fromString(self::string($data, 'commandId')),
            CommandStatus::fromString(self::string($data, 'status')),
        );
    }

    /** @param array<string, mixed> $data
     *  @return list<OrderEvent>
     */
    public static function events(array $data): array
    {
        $events = [];
        foreach (self::list($data, 'data', '$') as $index => $value) {
            $path = '$.data[' . $index . ']';
            $event = self::object($value, $path);
            $order = self::object($event['order'] ?? null, $path . '.order');
            $events[] = new OrderEvent(
                EventId::fromString(self::string($event, 'id', $path)),
                OrderEventType::fromString(self::string($event, 'orderEventType', $path)),
                OrderId::fromString(self::string($order, 'id', $path . '.order')),
                self::optionalDate($event, 'occurredAt', $path),
            );
        }

        return $events;
    }

    /** @param list<mixed> $data
     *  @return list<DeliveryMethod>
     */
    public static function deliveryMethods(array $data): array
    {
        $items = [];
        foreach ($data as $index => $value) {
            $path = '$[' . $index . ']';
            $item = self::object($value, $path);
            $items[] = new DeliveryMethod(self::string($item, 'code', $path), self::string($item, 'name', $path));
        }

        return $items;
    }

    /** @param list<mixed> $data
     *  @return list<string>
     */
    public static function legacyDeliveryMethods(array $data): array
    {
        $result = [];
        foreach ($data as $index => $value) {
            if (!is_string($value)) {
                throw new ResponseMappingException('$[' . $index . ']', 'must be a string');
            }
            $result[] = $value;
        }

        return $result;
    }

    /** @param array<string, mixed> $data */
    public static function refund(array $data): RefundResult
    {
        return new RefundResult(
            isset($data['refundAmount']) ? self::money(self::object($data['refundAmount'], '$.refundAmount'), '$.refundAmount') : null,
            RefundStatus::fromString(self::string($data, 'status')),
            self::nullableString($data, 'description'),
        );
    }

    /** @param array<string, mixed> $data */
    public static function action(array $data): ActionResult
    {
        return new ActionResult(self::nullableString($data, 'message'));
    }

    /** @param array<string, mixed> $data */
    public static function return(array $data, string $path = '$'): ReturnDetails
    {
        return new ReturnDetails(
            ReturnId::fromString(self::string($data, 'id', $path)),
            OrderId::fromString(self::string($data, 'orderId', $path)),
            ReturnStatus::fromString(self::string($data, 'status', $path)),
            self::object($data['client'] ?? null, $path . '.client'),
            self::objectList($data, 'orderLines', $path),
            self::optionalDate($data, 'createdAt', $path),
            self::optionalDate($data, 'deliveredAt', $path),
            self::optionalDate($data, 'expiresAt', $path),
            ResponseHydrator::additionalData($data, ['id', 'orderId', 'status', 'client', 'orderLines', 'createdAt', 'deliveredAt', 'expiresAt']),
        );
    }

    /** @param array<string, mixed> $data
     *  @return PageResult<ReturnDetails>
     */
    public static function returns(array $data): PageResult
    {
        $items = [];
        foreach (self::list($data, 'data', '$') as $index => $item) {
            $items[] = self::return(self::object($item, '$.data[' . $index . ']'), '$.data[' . $index . ']');
        }

        return new PageResult($items, self::page(self::object($data['page'] ?? null, '$.page')));
    }

    /** @param array<string, mixed> $data */
    public static function claim(array $data, string $path = '$'): ClaimDetails
    {
        return new ClaimDetails(
            ClaimId::fromString(self::string($data, 'claimId', $path)),
            ClaimState::fromString(self::string($data, 'state', $path)),
            self::nullableString($data, 'resolution'),
            self::optionalObject($data, 'claimant', $path),
            self::object($data['relatedOrder'] ?? null, $path . '.relatedOrder'),
            self::objectList($data, 'orderLines', $path),
            self::optionalDate($data, 'createdAt', $path),
            self::optionalDate($data, 'expiresAt', $path),
            self::optionalDate($data, 'updatedAt', $path),
            ResponseHydrator::additionalData($data, ['claimId', 'state', 'resolution', 'claimant', 'relatedOrder', 'orderLines', 'createdAt', 'expiresAt', 'updatedAt']),
        );
    }

    /** @param array<string, mixed> $data
     *  @return PageResult<ClaimDetails>
     */
    public static function claims(array $data): PageResult
    {
        $items = [];
        foreach (self::list($data, 'items', '$') as $index => $item) {
            $items[] = self::claim(self::object($item, '$.items[' . $index . ']'), '$.items[' . $index . ']');
        }

        return new PageResult($items, self::page(self::object($data['page'] ?? null, '$.page')));
    }

    /** @param array<string, mixed> $data
     *  @return list<ClaimType>
     */
    public static function claimTypes(array $data): array
    {
        $items = [];
        foreach (self::list($data, 'data', '$') as $index => $value) {
            $path = '$.data[' . $index . ']';
            $item = self::object($value, $path);
            $items[] = new ClaimType(ClaimId::fromString(self::string($item, 'id', $path)), self::string($item, 'description', $path));
        }

        return $items;
    }

    /** @param array<string, mixed> $data */
    private static function money(array $data, string $path): Money
    {
        $amount = $data['amount'] ?? null;
        if (!is_int($amount) && !is_float($amount)) {
            throw new ResponseMappingException($path . '.amount', 'must be a number');
        }
        $currency = self::string($data, 'currency', $path);
        if ($currency !== Currency::PLN->value) {
            throw new ResponseMappingException($path . '.currency', 'unsupported currency');
        }

        return Money::fromDecimal(number_format($amount, 2, '.', ''), Currency::PLN);
    }

    /** @param array<string, mixed> $data */
    private static function page(array $data): Page
    {
        return new Page(ResponseHydrator::integer($data, 'offset'), ResponseHydrator::integer($data, 'limit'), ResponseHydrator::integer($data, 'total'));
    }

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $path): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new ResponseMappingException($path, 'must be a JSON object');
        }
        $result = [];
        foreach ($value as $key => $item) {
            if (!is_string($key)) {
                throw new ResponseMappingException($path, 'must use string keys');
            }
            $result[$key] = $item;
        }

        return $result;
    }

    /** @param array<string, mixed> $data
     *  @return array<string, mixed>
     */
    private static function optionalObject(array $data, string $field, string $path): array
    {
        return !isset($data[$field]) ? [] : self::object($data[$field], $path . '.' . $field);
    }

    /** @param array<string, mixed> $data
     *  @return list<mixed>
     */
    private static function list(array $data, string $field, string $path): array
    {
        return ResponseHydrator::list($data, $field, $path);
    }

    /** @param array<string, mixed> $data
     *  @return list<array<string, mixed>>
     */
    private static function objectList(array $data, string $field, string $path): array
    {
        $items = [];
        foreach (isset($data[$field]) ? self::list($data, $field, $path) : [] as $index => $item) {
            $items[] = self::object($item, $path . '.' . $field . '[' . $index . ']');
        }

        return $items;
    }

    /** @param array<string, mixed> $data */
    private static function string(array $data, string $field, string $path = '$'): string
    {
        return ResponseHydrator::string($data, $field, $path);
    }

    /** @param array<string, mixed> $data */
    private static function nullableString(array $data, string $field): ?string
    {
        return ResponseHydrator::nullableString($data, $field);
    }

    /** @param array<string, mixed> $data */
    private static function optionalDate(array $data, string $field, string $path): ?DateTimeImmutable
    {
        return !isset($data[$field]) ? null : ResponseHydrator::dateTime($data, $field, $path);
    }
}
