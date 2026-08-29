<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Request;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;

final class OrderListOptions
{
    /** @param list<string> $statuses
     *  @param list<string> $paymentStatuses
     *  @param list<string> $sort
     */
    public function __construct(
        public readonly array $statuses = [],
        public readonly array $paymentStatuses = [],
        public readonly int $limit = 10,
        public readonly int $offset = 0,
        public readonly array $sort = [],
        public readonly ?UtcDateTime $updatedAtGte = null,
        public readonly ?ResponseLanguage $language = null,
    ) {
        RequestValidator::integerRange($limit, 0, 30, 'orders.limit');
        if ($offset < 0) {
            throw new InvalidRequestException('orders.offset', 'must be non-negative');
        }
        RequestValidator::stringList($statuses, 'orders.statuses');
        RequestValidator::stringList($paymentStatuses, 'orders.paymentStatuses');
        RequestValidator::stringList($sort, 'orders.sort');
        foreach ($paymentStatuses as $value) {
            if (!in_array($value, ['PAID', 'NOT_PAID'], true)) {
                throw new InvalidRequestException('orders.paymentStatuses', 'contains an unsupported value');
            }
        }
        foreach ($sort as $value) {
            if (!in_array($value, ['createdAt', '-createdAt', 'updatedAt', '-updatedAt', 'status', '-status'], true)) {
                throw new InvalidRequestException('orders.sort', 'contains an unsupported value');
            }
        }
        if (count($sort) > 3) {
            throw new InvalidRequestException('orders.sort', 'must contain at most 3 values');
        }
    }
}
