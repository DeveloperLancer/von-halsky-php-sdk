<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\PostSaleResponseHydrator;
use DevLancer\VonHalsky\Model\Claim\ClaimDetails;
use DevLancer\VonHalsky\Model\Claim\ClaimType;
use DevLancer\VonHalsky\Model\PostSale\ActionResult;
use DevLancer\VonHalsky\Model\PostSale\ResolutionDescription;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\Request\ClaimListOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\ValueObject\ClaimId;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

final class ClaimsResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly ?OrganizationId $organizationId = null,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /**
     * Requires OAuth scope `api:orders:read`.
     *
     * @return ApiResponse<list<ClaimType>>
     */
    public function types(?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', '/v1/orders/claim-types', [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::claimTypes($this->requiredObject($response, 'getOrdersClaimTypesDictionaryV1')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:read`.
     *
     * @return ApiResponse<PageResult<ClaimDetails>>
     */
    public function list(?ClaimListOptions $options = null): ApiResponse
    {
        $options ??= new ClaimListOptions();
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit, 'offset' => $options->offset];
        if ($options->customerEmail !== null) {
            $query['customerEmail'] = $options->customerEmail;
        }
        if ($options->customerPhoneNumber !== null) {
            $query['customerPhoneNumber'] = $options->customerPhoneNumber;
        }
        if ($options->resolutions !== []) {
            $query['resolution'] = $options->resolutions;
        }
        if ($options->states !== []) {
            $query['state'] = $options->states;
        }
        if ($options->submissionDateFrom !== null) {
            $query['submissionDateFrom'] = $options->submissionDateFrom->toAtomString();
        }
        if ($options->submissionDateTo !== null) {
            $query['submissionDateTo'] = $options->submissionDateTo->toAtomString();
        }
        if ($options->sort !== []) {
            $query['sort'] = $options->sort;
        }
        $response = $this->executor->execute('GET', $this->basePath(), $query, self::language($options->language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::claims($this->requiredObject($response, 'getClaimsForOrganizationV1')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:read`.
     *
     * @return ApiResponse<ClaimDetails>
     */
    public function get(OrderId $orderId, ClaimId $claimId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->claimPath($orderId, $claimId), [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::claim($this->requiredObject($response, 'getClaimByIdV1')), $response);
    }

    /**
     * Requires OAuth scope `api:orders:write`.
     *
     * @return ApiResponse<ActionResult>
     */
    public function reject(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->action($orderId, $claimId, 'reject', 'rejectClaimV1', $request, $language);
    }

    /**
     * Requires OAuth scope `api:orders:write`.
     *
     * @return ApiResponse<ActionResult>
     */
    public function partialRefund(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->action($orderId, $claimId, 'partial-refund', 'partialRefundClaimV1', $request, $language);
    }

    /**
     * Requires OAuth scope `api:orders:write`.
     *
     * @return ApiResponse<ActionResult>
     */
    public function refund(OrderId $orderId, ClaimId $claimId, ?ResolutionDescription $request = null, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->action($orderId, $claimId, 'refund', 'refundClaimV1', $request, $language);
    }

    /** @return ApiResponse<ActionResult> */
    private function action(OrderId $orderId, ClaimId $claimId, string $action, string $operationId, ?ResolutionDescription $request, ?ResponseLanguage $language): ApiResponse
    {
        $path = $this->claimPath($orderId, $claimId) . '/' . $action;
        $response = $request === null
            ? $this->executor->execute('POST', $path, [], self::language($language))
            : $this->executor->executeDto('POST', $path, $request, [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::action($this->requiredObject($response, $operationId)), $response);
    }

    private function basePath(): string
    {
        if ($this->organizationId === null) {
            throw new InvalidRequestException('organizationId', 'select an organization with forOrganization()');
        }

        return '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/claims';
    }

    private function claimPath(OrderId $orderId, ClaimId $claimId): string
    {
        if ($this->organizationId === null) {
            throw new InvalidRequestException('organizationId', 'select an organization with forOrganization()');
        }

        return '/v1/organizations/' . rawurlencode($this->organizationId->value)
            . '/orders/' . rawurlencode($orderId->value)
            . '/claims/' . rawurlencode($claimId->value);
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
