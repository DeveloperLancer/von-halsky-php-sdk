<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\PostSaleResponseHydrator;
use DevLancer\VonHalsky\Model\Order\OrderCommand;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Isolated API surface for operations deprecated by contract 1.5.11.
 *
 * @deprecated Planned for removal in SDK 2.0. Prefer current resources whenever an alternative exists.
 */
final class DeprecatedResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly ?OrganizationId $organizationId = null,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /** Legacy delivery codes. Use OrdersResource::deliveryMethods() for v2 names and codes.
     *  @deprecated Use OrdersResource::deliveryMethods(). Planned removal in SDK 2.0.
     *  @return ApiResponse<list<string>>
     */
    public function deliveryMethods(?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', '/v1/orders/delivery-methods', [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::legacyDeliveryMethods($this->decoder->decodeList($response, 'getOrdersDeliveryMethodsV1')), $response);
    }

    /** The production contract documents no replacement for refusing an order.
     *  @deprecated No contract replacement is available. Planned removal in SDK 2.0.
     *  @return ApiResponse<OrderCommand>
     */
    public function refuseOrder(OrderId $orderId, ?ResponseLanguage $language = null): ApiResponse
    {
        if ($this->organizationId === null) {
            throw new InvalidRequestException('organizationId', 'select an organization with forOrganization()');
        }
        $path = '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/orders/' . rawurlencode($orderId->value) . '/refuse';
        $response = $this->executor->execute('POST', $path, [], self::language($language));
        $object = $this->decoder->decodeObject($response, 'postOrdersRefuseByIdV1');
        if ($object === null) {
            throw new ResponseMappingException('$', 'response cannot be empty');
        }

        return ApiResponse::fromResponse(PostSaleResponseHydrator::command($object), $response);
    }

    /** @return array<string, string> */
    private static function language(?ResponseLanguage $language): array
    {
        return $language === null ? [] : ['Accept-Language' => $language->value];
    }
}
