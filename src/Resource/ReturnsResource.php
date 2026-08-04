<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\PostSaleResponseHydrator;
use DevLancer\VonHalsky\Model\PostSale\ActionResult;
use DevLancer\VonHalsky\Model\ReturnOrder\ReturnDetails;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Request\ReturnListOptions;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\ValueObject\ReturnId;

final class ReturnsResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly OrganizationId $organizationId,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /** @return ApiResponse<PageResult<ReturnDetails>> */
    public function list(?ReturnListOptions $options = null): ApiResponse
    {
        return $this->listAt($this->basePath(), 'getReturnsForOrganizationV1', $options);
    }

    /** @return ApiResponse<PageResult<ReturnDetails>> */
    public function forOrder(OrderId $orderId, ?ReturnListOptions $options = null): ApiResponse
    {
        return $this->listAt($this->ordersPath() . '/' . rawurlencode($orderId->value) . '/returns', 'getReturnsByOrderIdV1', $options);
    }

    /** @return ApiResponse<ReturnDetails> */
    public function get(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->returnPath($returnId), [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::return($this->requiredObject($response, 'getReturnByIdV1')), $response);
    }

    /** @return ApiResponse<ActionResult> */
    public function accept(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->action($returnId, 'accept', 'acceptReturnV1', $language);
    }

    /** @return ApiResponse<ActionResult> */
    public function reject(ReturnId $returnId, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->action($returnId, 'reject', 'rejectReturnV1', $language);
    }

    /** @return ApiResponse<PageResult<ReturnDetails>> */
    private function listAt(string $path, string $operationId, ?ReturnListOptions $options): ApiResponse
    {
        $options ??= new ReturnListOptions();
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit, 'offset' => $options->offset];
        if ($options->statuses !== []) {
            $query['status'] = $options->statuses;
        }
        $response = $this->executor->execute('GET', $path, $query, self::language($options->language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::returns($this->requiredObject($response, $operationId)), $response);
    }

    /** @return ApiResponse<ActionResult> */
    private function action(ReturnId $returnId, string $action, string $operationId, ?ResponseLanguage $language): ApiResponse
    {
        $response = $this->executor->execute('POST', $this->returnPath($returnId) . '/' . $action, [], self::language($language));

        return ApiResponse::fromResponse(PostSaleResponseHydrator::action($this->requiredObject($response, $operationId)), $response);
    }

    private function basePath(): string
    {
        return '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/returns';
    }

    private function ordersPath(): string
    {
        return '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/orders';
    }

    private function returnPath(ReturnId $returnId): string
    {
        return $this->basePath() . '/' . rawurlencode($returnId->value);
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
