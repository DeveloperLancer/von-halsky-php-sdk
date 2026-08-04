<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Internal;

use DateTimeImmutable;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Model\Attachment\AttachmentInfo;
use DevLancer\VonHalsky\Model\Offer\CommandDetails;
use DevLancer\VonHalsky\Model\Offer\CommandError;
use DevLancer\VonHalsky\Model\Offer\CommandHandle;
use DevLancer\VonHalsky\Model\Offer\CommandStatus;
use DevLancer\VonHalsky\Model\Offer\DepositType;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\OfferEvent;
use DevLancer\VonHalsky\Model\Offer\OfferEventType;
use DevLancer\VonHalsky\Model\Offer\OfferStatus;
use DevLancer\VonHalsky\Model\Offer\ProductHint;
use DevLancer\VonHalsky\Pagination\Page;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\Currency;
use DevLancer\VonHalsky\ValueObject\EventId;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;

/** @internal Strict phase 6 response mapping. */
final class OfferResponseHydrator
{
    /** @param array<string, mixed> $data */
    public static function offer(array $data, string $path = '$'): OfferDetails
    {
        $offer = self::object($data['offer'] ?? null, $path . '.offer');
        $metadata = self::optionalObject($data, 'metadata', $path);

        return new OfferDetails(
            OfferId::fromString(self::string($offer, 'id', $path . '.offer')),
            OfferStatus::fromString(self::string($offer, 'status', $path . '.offer')),
            self::object($offer['product'] ?? null, $path . '.offer.product'),
            self::object($offer['stock'] ?? null, $path . '.offer.stock'),
            self::object($offer['price'] ?? null, $path . '.offer.price'),
            self::optionalDate($offer, 'createdAt', $path . '.offer'),
            self::optionalDate($offer, 'updatedAt', $path . '.offer'),
            $metadata,
            ResponseHydrator::additionalData($offer, ['id', 'status', 'product', 'stock', 'price', 'createdAt', 'updatedAt']),
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return PageResult<OfferDetails>
     */
    public static function offers(array $data): PageResult
    {
        $items = [];
        foreach (self::list($data, 'data', '$') as $index => $item) {
            $items[] = self::offer(self::object($item, '$.data[' . $index . ']'), '$.data[' . $index . ']');
        }

        return new PageResult($items, self::page(self::object($data['page'] ?? null, '$.page')), ResponseHydrator::additionalData($data, ['data', 'page']));
    }

    /** @param array<string, mixed> $data */
    public static function handle(array $data): CommandHandle
    {
        return new CommandHandle(
            CommandId::fromString(self::string($data, 'commandId')),
            isset($data['offerId']) && is_string($data['offerId']) ? OfferId::fromString($data['offerId']) : null,
            self::nullableString($data, 'externalId'),
            self::nullableString($data, 'status'),
            ResponseHydrator::additionalData($data, ['commandId', 'offerId', 'externalId', 'status']),
        );
    }

    /** @param array<string, mixed> $data */
    public static function command(array $data): CommandDetails
    {
        $errors = [];
        foreach (self::optionalList($data, 'errors', '$') as $index => $error) {
            $error = self::object($error, '$.errors[' . $index . ']');
            $errors[] = new CommandError(
                self::string($error, 'message', '$.errors[' . $index . ']'),
                self::nullableString($error, 'fieldName'),
                self::nullableString($error, 'elementId'),
            );
        }

        return new CommandDetails(
            CommandId::fromString(self::string($data, 'commandId')),
            CommandStatus::fromString(self::string($data, 'status')),
            $errors,
        );
    }

    /**
     * @param array<string, mixed> $data
     * @return list<OfferEvent>
     */
    public static function events(array $data): array
    {
        $events = [];
        foreach (self::list($data, 'data', '$') as $index => $event) {
            $path = '$.data[' . $index . ']';
            $event = self::object($event, $path);
            $offer = self::object($event['offer'] ?? null, $path . '.offer');
            $events[] = new OfferEvent(
                EventId::fromString(self::string($event, 'id', $path)),
                OfferEventType::fromString(self::string($event, 'offerEventType', $path)),
                OfferId::fromString(self::string($offer, 'id', $path . '.offer')),
                self::nullableString($offer, 'externalId'),
                self::optionalDate($event, 'occurredAt', $path),
            );
        }

        return $events;
    }

    /**
     * @param array<string, mixed> $data
     * @return PageResult<ProductHint>
     */
    public static function hints(array $data): PageResult
    {
        $items = [];
        foreach (self::list($data, 'data', '$') as $index => $hint) {
            $hint = self::object($hint, '$.data[' . $index . ']');
            $gpsr = self::optionalList($hint, 'gpsr', '$.data[' . $index . ']');
            $gpsrObjects = [];
            foreach ($gpsr as $gpsrIndex => $value) {
                $gpsrObjects[] = self::object($value, '$.data[' . $index . '].gpsr[' . $gpsrIndex . ']');
            }
            $items[] = new ProductHint(self::optionalObject($hint, 'product', '$.data[' . $index . ']'), $gpsrObjects);
        }

        return new PageResult($items, self::page(self::object($data['page'] ?? null, '$.page')));
    }

    /**
     * @param array<string, mixed> $data
     * @return list<DepositType>
     */
    public static function deposits(array $data): array
    {
        $result = [];
        foreach (self::list($data, 'data', '$') as $index => $item) {
            $path = '$.data[' . $index . ']';
            $item = self::object($item, $path);
            $type = self::object($item['depositType'] ?? null, $path . '.depositType');
            $price = self::object($type['price'] ?? null, $path . '.depositType.price');
            $amount = $price['amount'] ?? null;
            if (!is_int($amount) && !is_float($amount)) {
                throw new ResponseMappingException($path . '.depositType.price.amount', 'must be a number');
            }
            $currency = self::string($price, 'currency', $path . '.depositType.price');
            if ($currency !== Currency::PLN->value) {
                throw new ResponseMappingException($path . '.depositType.price.currency', 'unsupported currency');
            }
            $result[] = new DepositType(
                self::string($type, 'id', $path . '.depositType'),
                self::string($item, 'name', $path),
                Money::fromDecimal(number_format($amount, 2, '.', ''), Currency::PLN),
            );
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $data
     * @return PageResult<AttachmentInfo>
     */
    public static function attachments(array $data): PageResult
    {
        $items = [];
        foreach (self::list($data, 'data', '$') as $index => $item) {
            $path = '$.data[' . $index . ']';
            $item = self::object($item, $path);
            $items[] = new AttachmentInfo(
                AttachmentId::fromString(self::string($item, 'id', $path)),
                self::string($item, 'name', $path),
                self::string($item, 'attachmentType', $path),
                self::optionalDate($item, 'createdAt', $path),
                self::nullableString($item, 'url'),
            );
        }

        return new PageResult($items, self::page(self::object($data['page'] ?? null, '$.page')));
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

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function optionalObject(array $data, string $field, string $path): array
    {
        if (!isset($data[$field])) {
            return [];
        }

        return self::object($data[$field], $path . '.' . $field);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<mixed>
     */
    private static function list(array $data, string $field, string $path): array
    {
        return ResponseHydrator::list($data, $field, $path);
    }

    /**
     * @param array<string, mixed> $data
     * @return list<mixed>
     */
    private static function optionalList(array $data, string $field, string $path): array
    {
        if (!isset($data[$field])) {
            return [];
        }
        if (!is_array($data[$field]) || !array_is_list($data[$field])) {
            throw new ResponseMappingException($path . '.' . $field, 'must be an array');
        }

        return $data[$field];
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
