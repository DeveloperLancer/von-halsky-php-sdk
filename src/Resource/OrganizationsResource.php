<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\DomainResponseHydrator;
use DevLancer\VonHalsky\Model\Organization\Organization;
use DevLancer\VonHalsky\Request\OrganizationListOptions;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;

/** Read-only access to organizations available for the current token. */
final class OrganizationsResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /** @return ApiResponse<list<Organization>> */
    public function list(?OrganizationListOptions $options = null): ApiResponse
    {
        $options ??= new OrganizationListOptions();
        $headers = $options->language === null ? [] : ['Accept-Language' => $options->language->value];
        $response = $this->executor->execute('GET', '/v1/organizations', [], $headers);
        $data = DomainResponseHydrator::organizations(
            $this->decoder->decodeList($response, 'getOrganizationsV1'),
        );

        return ApiResponse::fromResponse($data, $response);
    }
}
