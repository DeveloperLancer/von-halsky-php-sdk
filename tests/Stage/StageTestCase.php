<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\OrganizationContext;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;
use LogicException;
use PHPUnit\Framework\TestCase;

abstract class StageTestCase extends TestCase
{
    private static ?StageTestConfig $configuration = null;
    private static ?VonHalskyClient $client = null;
    private static ?OrganizationId $organizationId = null;
    private static ?CategoryId $leafCategoryId = null;

    final protected function stageConfig(): StageTestConfig
    {
        return self::$configuration ??= StageTestConfig::load();
    }

    final protected function stageClient(): VonHalskyClient
    {
        if (self::$client !== null) {
            return self::$client;
        }

        $environment = Environment::stage();
        self::assertStageEnvironment($environment);
        $http = SymfonyHttpClientFactory::create();
        $oauth = new OAuthClient(
            $environment,
            $http->httpClient,
            $http->requestFactory,
            $http->streamFactory,
        );
        $configuration = $this->stageConfig();
        $tokens = $oauth->requestClientCredentialsToken(
            $configuration->clientId,
            $configuration->clientSecret,
            [
                OAuthScope::OpenId,
                OAuthScope::CategoriesRead,
                OAuthScope::OffersRead,
                OAuthScope::OffersWrite,
                OAuthScope::OrdersRead,
            ],
        );

        self::$client = new VonHalskyClient(
            $environment,
            new StaticTokenProvider($tokens->accessToken),
            $http,
        );

        return self::$client;
    }

    final protected function stageOrganization(): OrganizationContext
    {
        return $this->stageClient()->forOrganization($this->stageOrganizationId());
    }

    final protected function stageOrganizationId(): OrganizationId
    {
        if (self::$organizationId !== null) {
            return self::$organizationId;
        }

        $configured = $this->stageConfig()->organizationId;
        if ($configured !== null) {
            return self::$organizationId = OrganizationId::fromString($configured);
        }

        $organizations = $this->stageClient()->organizations()->list()->data;
        $available = [];
        foreach ($organizations as $organization) {
            if ($organization->id !== null) {
                $available[] = $organization->id;
            }
        }
        if (count($available) !== 1) {
            throw new LogicException('Stage tests require exactly one available organization or an explicit organization_id.');
        }

        return self::$organizationId = $available[0];
    }

    final protected function stageLeafCategoryId(): CategoryId
    {
        if (self::$leafCategoryId !== null) {
            return self::$leafCategoryId;
        }

        $configured = $this->stageConfig()->leafCategoryId;
        if ($configured !== null) {
            return self::$leafCategoryId = CategoryId::fromString($configured);
        }

        throw new LogicException('Stage offer tests require leaf_category_id to prevent mixing product hints with a different category.');
    }

    private static function assertStageEnvironment(Environment $environment): void
    {
        if ($environment->id !== 'stage'
            || $environment->apiBaseUrl !== 'https://stage-api.inpost-group.com/inpsa'
            || $environment->authorizationUrl !== 'https://stage-account.inpost-group.com/oauth2/authorize'
            || $environment->tokenUrl !== 'https://stage-account.inpost-group.com/oauth2/token'
        ) {
            throw new LogicException('Stage integration tests refused a non-Stage environment.');
        }
    }
}
