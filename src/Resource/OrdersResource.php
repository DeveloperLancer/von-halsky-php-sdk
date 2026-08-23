<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\PostSaleResponseHydrator;
use DevLancer\VonHalsky\Model\Order\DeliveryMethod;
use DevLancer\VonHalsky\Model\Order\OrderCommand;
use DevLancer\VonHalsky\Model\Order\OrderDetails;
use DevLancer\VonHalsky\Model\Order\OrderEvent;
use DevLancer\VonHalsky\Model\Order\RefundRequest;
use DevLancer\VonHalsky\Model\Order\RefundResult;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\Request\OrderEventsOptions;
use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Current order operations. */
final class OrdersResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly ?OrganizationId $organizationId = null,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /** @return ApiResponse<list<DeliveryMethod>> */
    public function deliveryMethods(?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', '/v2/orders/delivery-methods', [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::deliveryMethods($this->decoder->decodeList($response, 'getOrdersDeliveryMethodsV2')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:read`.
     *
     * @return ApiResponse<PageResult<OrderDetails>>
     */
    public function list(?OrderListOptions $options = null): ApiResponse
    {
        $options ??= new OrderListOptions();
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit, 'offset' => $options->offset];
        if ($options->statuses !== []) {
            $query['orderStatus'] = $options->statuses;
        }
        if ($options->paymentStatuses !== []) {
            $query['paymentStatus'] = $options->paymentStatuses;
        }
        if ($options->sort !== []) {
            $query['sort'] = $options->sort;
        }
        if ($options->updatedAtGte !== null) {
            $query['updatedAtGte'] = $options->updatedAtGte->toAtomString();
        }
        $response = $this->executor->execute('GET', $this->basePath(), $query, self::language($options->language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::orders($this->requiredObject($response, 'getOrdersV1')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:read`.
     *
     * @return ApiResponse<OrderDetails>
     */
    public function get(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->orderPath($orderId), [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::order($this->requiredObject($response, 'getOrdersByIdV1')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:write`.
     *
     * @return ApiResponse<OrderCommand>
     */
    public function accept(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('POST', $this->orderPath($orderId) . '/accept', [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::command($this->requiredObject($response, 'postOrdersAcceptByIdV1')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:read`.
     *
     * @return ApiResponse<OrderCommand>
     */
    public function command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->basePath() . '/commands/' . rawurlencode($commandId->value), [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::command($this->requiredObject($response, 'getOrdersCommandsByIdV1')), $response);
    }

    /** Events are returned newest-first. Requires OAuth scope `api:orders:read`.
     *  @return ApiResponse<list<OrderEvent>>
     */
    public function events(?OrderEventsOptions $options = null): ApiResponse
    {
        $options ??= new OrderEventsOptions();
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit];
        if ($options->untilId !== null) {
            $query['untilId'] = $options->untilId->value;
        }
        if ($options->types !== []) {
            $query['eventType'] = $options->types;
        }
        $response = $this->executor->execute('GET', $this->basePath() . '/events', $query, self::language($options->language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::events($this->requiredObject($response, 'getOrdersEventsV1')), $response);
    }

    /** Null requests a full refund; a RefundRequest requests an exact partial amount.
     *  Requires OAuth scope `api:orders:write`.
     *  @return ApiResponse<RefundResult>
     */
    public function refund(OrderId $orderId, ?RefundRequest $request = null, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $request === null
            ? $this->executor->execute('POST', $this->orderPath($orderId) . '/refund', [], self::language($language))
            : $this->executor->executeDto('POST', $this->orderPath($orderId) . '/refund', $request, [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::refund($this->requiredObject($response, 'postOrdersRefundByOrderIdV1')), $response);
    }

    private function basePath(): string
    {
        if ($this->organizationId === null) {
            throw new InvalidRequestException('organizationId', 'select an organization with forOrganization()');
        }

        return '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/orders';
    }

    private function orderPath(OrderId $orderId): string
    {
        return $this->basePath() . '/' . rawurlencode($orderId->value);
    }

    /** @return array<string, string> */
    private static function language(?ResponseLanguage $language): array
    {
        return $language === null ? [] : ['Accept-Language' => $language->value];
    }

    /** @return array<string, mixed> */
    private function requiredObject(\Psr\Http\Message\ResponseInterface $response, string $operationId): array
    {
        $object = $this->decoder->decodeObject($response, $operationId);
        if ($object === null) {
            throw new ResponseMappingException('$', 'response cannot be empty');
        }

        return $object;
    }
}
