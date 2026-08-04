<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky;

use DevLancer\VonHalsky\Auth\TokenProviderInterface;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\HttpClientDependencies;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\Resource\AttachmentsResource;
use DevLancer\VonHalsky\Resource\CategoriesResource;
use DevLancer\VonHalsky\Resource\ClaimsResource;
use DevLancer\VonHalsky\Resource\DeprecatedResource;
use DevLancer\VonHalsky\Resource\OffersResource;
use DevLancer\VonHalsky\Resource\OrdersResource;
use DevLancer\VonHalsky\Resource\OrganizationsResource;
use DevLancer\VonHalsky\Resource\ReturnsResource;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Public immutable entry point for Von Halsky API resources. */
final class VonHalskyClient
{
    private readonly RequestExecutor $executor;

    public function __construct(
        Environment $environment,
        TokenProviderInterface $tokenProvider,
        HttpClientDependencies $http,
    ) {
        $this->executor = new RequestExecutor(
            $environment,
            $http->httpClient,
            $http->requestFactory,
            $http->streamFactory,
            $tokenProvider,
        );
    }

    /** Creates a client with the secure default Symfony PSR-18 transport. */
    public static function create(
        TokenProviderInterface $tokenProvider,
        ?Environment $environment = null,
        float $timeout = 30.0,
    ): self {
        return new self(
            $environment ?? Environment::production(),
            $tokenProvider,
            SymfonyHttpClientFactory::create($timeout),
        );
    }

    public function organizations(): OrganizationsResource
    {
        return new OrganizationsResource($this->executor);
    }

    public function categories(): CategoriesResource
    {
        return new CategoriesResource($this->executor);
    }

    /** Global offer dictionaries. Select an organization for scoped operations. */
    public function offers(): OffersResource
    {
        return new OffersResource($this->executor);
    }

    /** Global order dictionaries. Select an organization for scoped operations. */
    public function orders(): OrdersResource
    {
        return new OrdersResource($this->executor);
    }

    /** Global claim dictionaries. Select an organization for scoped operations. */
    public function claims(): ClaimsResource
    {
        return new ClaimsResource($this->executor);
    }

    /** Explicitly isolated operations deprecated by the supported API contract. */
    public function deprecated(): DeprecatedResource
    {
        return new DeprecatedResource($this->executor);
    }

    /** @internal Used by the immutable organization context. */
    public function offersForOrganization(OrganizationId $organizationId): OffersResource
    {
        return new OffersResource($this->executor, $organizationId);
    }

    /** @internal Used by the immutable organization context. */
    public function attachmentsForOrganization(OrganizationId $organizationId): AttachmentsResource
    {
        return new AttachmentsResource($this->executor, $organizationId);
    }

    /** @internal Used by the immutable organization context. */
    public function ordersForOrganization(OrganizationId $organizationId): OrdersResource
    {
        return new OrdersResource($this->executor, $organizationId);
    }

    /** @internal Used by the immutable organization context. */
    public function returnsForOrganization(OrganizationId $organizationId): ReturnsResource
    {
        return new ReturnsResource($this->executor, $organizationId);
    }

    /** @internal Used by the immutable organization context. */
    public function claimsForOrganization(OrganizationId $organizationId): ClaimsResource
    {
        return new ClaimsResource($this->executor, $organizationId);
    }

    /** @internal Used by the immutable organization context. */
    public function deprecatedForOrganization(OrganizationId $organizationId): DeprecatedResource
    {
        return new DeprecatedResource($this->executor, $organizationId);
    }

    public function forOrganization(OrganizationId $organizationId): OrganizationContext
    {
        return new OrganizationContext($organizationId, $this);
    }
}
