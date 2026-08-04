<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky;

use DevLancer\VonHalsky\Resource\AttachmentsResource;
use DevLancer\VonHalsky\Resource\ClaimsResource;
use DevLancer\VonHalsky\Resource\DeprecatedResource;
use DevLancer\VonHalsky\Resource\OffersResource;
use DevLancer\VonHalsky\Resource\OrdersResource;
use DevLancer\VonHalsky\Resource\ReturnsResource;
use DevLancer\VonHalsky\ValueObject\OrganizationId;

/** Immutable organization selection for organization-scoped resources. */
final class OrganizationContext
{
    public function __construct(
        public readonly OrganizationId $organizationId,
        private readonly VonHalskyClient $client,
    ) {
    }

    /** Returns the global client without changing either object's organization state. */
    public function client(): VonHalskyClient
    {
        return $this->client;
    }

    public function offers(): OffersResource
    {
        return $this->client->offersForOrganization($this->organizationId);
    }

    public function attachments(): AttachmentsResource
    {
        return $this->client->attachmentsForOrganization($this->organizationId);
    }

    public function orders(): OrdersResource
    {
        return $this->client->ordersForOrganization($this->organizationId);
    }

    public function returns(): ReturnsResource
    {
        return $this->client->returnsForOrganization($this->organizationId);
    }

    public function claims(): ClaimsResource
    {
        return $this->client->claimsForOrganization($this->organizationId);
    }

    /** Explicitly isolated operations deprecated by the supported API contract. */
    public function deprecated(): DeprecatedResource
    {
        return $this->client->deprecatedForOrganization($this->organizationId);
    }
}
