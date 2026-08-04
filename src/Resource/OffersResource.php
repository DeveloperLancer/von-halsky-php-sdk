<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\OfferResponseHydrator;
use DevLancer\VonHalsky\Model\Offer\BatchCreateOffersRequest;
use DevLancer\VonHalsky\Model\Offer\CommandDetails;
use DevLancer\VonHalsky\Model\Offer\CommandHandle;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\DepositType;
use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\OfferEvent;
use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\ProductHint;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\Request\OfferEventsOptions;
use DevLancer\VonHalsky\Request\OfferListOptions;
use DevLancer\VonHalsky\Request\ProductHintOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\Serialization\RequestNormalizer;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\OfferId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** All global and organization-scoped offer operations from contract 1.5.11. */
final class OffersResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly ?OrganizationId $organizationId = null,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /** @return ApiResponse<list<DepositType>> */
    public function depositTypes(?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', '/v1/offers/deposit-types', [], self::language($language));
        $data = $this->requiredObject($response, 'getOffersDepositTypesV1');

        return ApiResponse::fromResponse(OfferResponseHydrator::deposits($data), $response);
    }

    /** @return ApiResponse<PageResult<OfferDetails>> */
    public function list(?OfferListOptions $options = null): ApiResponse
    {
        $options ??= new OfferListOptions();
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit, 'offset' => $options->offset];
        if ($options->statuses !== []) {
            $query['offerStatus'] = $options->statuses;
        }
        if ($options->sort !== []) {
            $query['sort'] = $options->sort;
        }
        $response = $this->executor->execute('GET', $this->basePath(), $query, self::language($options->language));

        return ApiResponse::fromResponse(OfferResponseHydrator::offers($this->requiredObject($response, 'getOffersV1')), $response);
    }

    /** @return ApiResponse<OfferDetails> */
    public function get(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->offerPath($offerId), [], self::language($language));

        return ApiResponse::fromResponse(OfferResponseHydrator::offer($this->requiredObject($response, 'getOffersByIdV1')), $response);
    }

    /**
     * HTTP 201 means that the command was accepted, not that the offer is ready.
     *
     * @return ApiResponse<CommandHandle>
     */
    public function create(CreateOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->executeDto('POST', $this->basePath(), $request, [], self::language($language));

        return ApiResponse::fromResponse(OfferResponseHydrator::handle($this->requiredObject($response, 'postOffersV1')), $response);
    }

    /** @return ApiResponse<list<CommandHandle>> */
    public function createBatch(BatchCreateOffersRequest $request, ?ResponseLanguage $language = null): ApiResponse
    {
        $normalizer = new RequestNormalizer();
        $payload = [];
        foreach ($request->items() as $item) {
            $payload[] = $normalizer->normalize($item);
        }
        $response = $this->executor->executeJson('POST', $this->basePath() . '/batch', $payload, [], self::language($language));
        $handles = [];
        foreach ($this->decoder->decodeList($response, 'postBatchOffersV1') as $index => $item) {
            $handles[] = OfferResponseHydrator::handle(self::object($item, '$[' . $index . ']'));
        }

        return ApiResponse::fromResponse($handles, $response);
    }

    /** @return ApiResponse<OfferDetails> */
    public function patch(OfferId $offerId, PatchOfferRequest $request, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->executeDto('PATCH', $this->offerPath($offerId), $request, [], self::language($language) + ['Content-Type' => 'application/merge-patch+json']);

        return ApiResponse::fromResponse(OfferResponseHydrator::offer($this->requiredObject($response, 'patchOffersByIdV1')), $response);
    }

    /**
     * @param list<OfferPriceUpdate> $updates
     * @return ApiResponse<list<CommandHandle>>
     */
    public function updatePrices(array $updates, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->batchUpdate('prices', $updates, 'patchUpdateOfferPricesBatchV1', $language);
    }

    /**
     * @param list<OfferStockUpdate> $updates
     * @return ApiResponse<list<CommandHandle>>
     */
    public function updateStocks(array $updates, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->batchUpdate('stocks', $updates, 'patchUpdateOfferStocksBatchV1', $language);
    }

    /** @return ApiResponse<CommandHandle> */
    public function updateAttributes(OfferId $offerId, OfferAttributesPatch $patch, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->executeDto('PATCH', $this->offerPath($offerId) . '/attributes', $patch, [], self::language($language));

        return ApiResponse::fromResponse(OfferResponseHydrator::handle($this->requiredObject($response, 'patchOfferAttributesByIdV1')), $response);
    }

    /** @return ApiResponse<CommandHandle> */
    public function close(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->lifecycle($offerId, 'close', 'postOffersCloseByOfferIdV1', $language);
    }

    /** @return ApiResponse<CommandHandle> */
    public function reopen(OfferId $offerId, ?ResponseLanguage $language = null): ApiResponse
    {
        return $this->lifecycle($offerId, 'reopen', 'postOffersReopenByOfferIdV1', $language);
    }

    /** @return ApiResponse<CommandDetails> */
    public function command(CommandId $commandId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->basePath() . '/commands/' . rawurlencode($commandId->value), [], self::language($language));

        return ApiResponse::fromResponse(OfferResponseHydrator::command($this->requiredObject($response, 'getOffersCommandsByIdV1')), $response);
    }

    /**
     * Events are returned newest-first by the API.
     *
     * @return ApiResponse<list<OfferEvent>>
     */
    public function events(?OfferEventsOptions $options = null): ApiResponse
    {
        $options ??= new OfferEventsOptions();
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit];
        if ($options->untilId !== null) {
            $query['untilId'] = $options->untilId->value;
        }
        if ($options->types !== []) {
            $query['eventType'] = $options->types;
        }
        $response = $this->executor->execute('GET', $this->basePath() . '/events', $query, self::language($options->language));

        return ApiResponse::fromResponse(OfferResponseHydrator::events($this->requiredObject($response, 'getOffersEventsV1')), $response);
    }

    /** @return ApiResponse<PageResult<ProductHint>> */
    public function hints(ProductHintOptions $options): ApiResponse
    {
        /** @var array<string, scalar|list<scalar>|null> $query */
        $query = ['limit' => $options->limit, 'offset' => $options->offset];
        if ($options->ean !== null) {
            $query['ean'] = (string) $options->ean;
        }
        if ($options->manufacturerProductNumber !== null) {
            $query['mpn'] = (string) $options->manufacturerProductNumber;
        }
        if ($options->name !== null) {
            $query['name'] = $options->name;
        }
        $response = $this->executor->execute('GET', $this->basePath() . '/hint', $query, self::language($options->language));

        return ApiResponse::fromResponse(OfferResponseHydrator::hints($this->requiredObject($response, 'getOffersHintV1')), $response);
    }

    /**
     * @param list<OfferPriceUpdate>|list<OfferStockUpdate> $updates
     * @return ApiResponse<list<CommandHandle>>
     */
    private function batchUpdate(string $path, array $updates, string $operationId, ?ResponseLanguage $language): ApiResponse
    {
        if ($updates === []) {
            throw new InvalidRequestException('offers.' . $path, 'must not be empty');
        }
        $normalizer = new RequestNormalizer();
        $payload = [];
        foreach ($updates as $update) {
            $payload[] = $normalizer->normalize($update);
        }
        $response = $this->executor->executeJson('PATCH', $this->basePath() . '/' . $path, $payload, [], self::language($language));
        $result = [];
        foreach ($this->decoder->decodeList($response, $operationId) as $index => $item) {
            $result[] = OfferResponseHydrator::handle(self::object($item, '$[' . $index . ']'));
        }

        return ApiResponse::fromResponse($result, $response);
    }

    /** @return ApiResponse<CommandHandle> */
    private function lifecycle(OfferId $offerId, string $action, string $operationId, ?ResponseLanguage $language): ApiResponse
    {
        $response = $this->executor->execute('POST', $this->offerPath($offerId) . '/' . $action, [], self::language($language));

        return ApiResponse::fromResponse(OfferResponseHydrator::handle($this->requiredObject($response, $operationId)), $response);
    }

    private function basePath(): string
    {
        if ($this->organizationId === null) {
            throw new InvalidRequestException('organizationId', 'select an organization with forOrganization()');
        }

        return '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/offers';
    }

    private function offerPath(OfferId $offerId): string
    {
        return $this->basePath() . '/' . rawurlencode($offerId->value);
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

    /** @return array<string, mixed> */
    private static function object(mixed $value, string $path): array
    {
        if (!is_array($value) || array_is_list($value)) {
            throw new ResponseMappingException($path, 'must be an object');
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
}
