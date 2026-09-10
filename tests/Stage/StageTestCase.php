<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\OAuthClient;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Auth\SystemClock;
use DevLancer\VonHalsky\Auth\TokenSet;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\NotFoundException;
use DevLancer\VonHalsky\Http\HttpClientDependencies;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Http\SymfonyHttpClientFactory;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\CommandDetails;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferDetails;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\OfferStatus;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\OrganizationContext;
use DevLancer\VonHalsky\Request\OfferEventsOptions;
use DevLancer\VonHalsky\Request\ProductHintOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Resource\OffersResource;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class StageTestCase extends TestCase
{
    private static ?StageTestConfig $configuration = null;
    private static ?HttpClientDependencies $http = null;
    private static ?TokenSet $tokens = null;
    private static ?VonHalskyClient $client = null;
    private static ?OrganizationId $organizationId = null;
    private static ?CategoryId $leafCategoryId = null;

    final protected function stageConfig(): StageTestConfig
    {
        return self::$configuration ??= StageTestConfig::load();
    }

    final protected function stageHttp(): HttpClientDependencies
    {
        return self::$http ??= SymfonyHttpClientFactory::create();
    }

    final protected function stageEnvironment(): Environment
    {
        $environment = Environment::stage();
        self::assertStageEnvironment($environment);

        return $environment;
    }

    final protected function stageOAuth(): OAuthClient
    {
        $http = $this->stageHttp();

        return new OAuthClient(
            $this->stageEnvironment(),
            $http->httpClient,
            $http->requestFactory,
            $http->streamFactory,
        );
    }

    /**
     * @param list<OAuthScope> $scopes
     */
    final protected function requestStageTokens(array $scopes): TokenSet
    {
        $configuration = $this->stageConfig();

        return $this->stageOAuth()->requestClientCredentialsToken(
            $configuration->clientId,
            $configuration->clientSecret,
            $scopes,
        );
    }

    /**
     * @param list<OAuthScope> $scopes
     */
    final protected function createStageClient(array $scopes): VonHalskyClient
    {
        $tokens = $this->requestStageTokens($scopes);

        return new VonHalskyClient(
            $this->stageEnvironment(),
            new StaticTokenProvider($tokens->accessToken),
            $this->stageHttp(),
        );
    }

    final protected function clientWithAccessToken(AccessToken $token): VonHalskyClient
    {
        return new VonHalskyClient(
            $this->stageEnvironment(),
            new StaticTokenProvider($token),
            $this->stageHttp(),
        );
    }

    final protected function stageClient(): VonHalskyClient
    {
        if (self::$client !== null
            && self::$tokens !== null
            && !self::$tokens->accessToken->isExpiring(new SystemClock(), 60)
        ) {
            return self::$client;
        }

        self::$tokens = $this->requestStageTokens([
            OAuthScope::OpenId,
            OAuthScope::CategoriesRead,
            OAuthScope::OffersRead,
            OAuthScope::OffersWrite,
            OAuthScope::OrdersRead,
        ]);
        self::$client = new VonHalskyClient(
            $this->stageEnvironment(),
            new StaticTokenProvider(self::$tokens->accessToken),
            $this->stageHttp(),
        );

        return self::$client;
    }

    final protected function stageTokens(): TokenSet
    {
        $this->stageClient();
        if (self::$tokens === null) {
            throw new LogicException('Stage tokens were not initialized.');
        }

        return self::$tokens;
    }

    final protected function stageRequestExecutor(): RequestExecutor
    {
        return new RequestExecutor(
            $this->stageEnvironment(),
            $this->stageHttp()->httpClient,
            $this->stageHttp()->requestFactory,
            $this->stageHttp()->streamFactory,
            new StaticTokenProvider($this->stageTokens()->accessToken),
        );
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

    final protected function uniqueExternalId(string $suffix = ''): string
    {
        return 'sdk-stage-' . bin2hex(random_bytes(12)) . $suffix;
    }

    /**
     * @param array{
     *     externalId?: string|null,
     *     name?: string,
     *     brand?: string,
     *     model?: string|null,
     *     superModel?: string|null,
     *     ean?: Ean,
     *     taxRateInfo?: string,
     *     gpsr?: GpsrInfo,
     *     daysToShip?: int,
     *     description?: string,
     *     images?: list<OfferImage>,
     *     attributes?: list<AttributeValue>
     * } $overrides
     */
    final protected function createSyntheticOffer(array $overrides = []): StageCreatedOffer
    {
        $configuration = $this->stageConfig();
        $offers = $this->stageOrganization()->offers();
        $product = $this->stageProductHint($offers, $configuration->productEan);
        $externalId = array_key_exists('externalId', $overrides) ? $overrides['externalId'] : $this->uniqueExternalId();
        $create = $offers->create(new CreateOfferRequest(
            new ProductProposal(
                $overrides['name'] ?? self::productString($product, 'name'),
                $overrides['description'] ?? self::stageDescription($product),
                $overrides['brand'] ?? self::productString($product, 'brand'),
                $this->stageLeafCategoryId(),
                $overrides['ean'] ?? new Ean($configuration->productEan),
                attributes: $overrides['attributes'] ?? $this->requiredCategoryAttributes(),
                model: $overrides['model'] ?? null,
                superModel: $overrides['superModel'] ?? null,
            ),
            new Stock(1),
            new Price(Money::fromDecimal('9.99'), $overrides['taxRateInfo'] ?? '23%'),
            $overrides['gpsr'] ?? GpsrInfo::notRequired(),
            $externalId,
            $overrides['daysToShip'] ?? 1,
            $overrides['images'] ?? [new OfferImage('sdk-stage-offer.png', $configuration->offerImageUrl, 1)],
        ));
        $offerId = $create->data->offerId;
        self::assertContains($create->statusCode, [200, 201, 202]);
        self::assertNotNull($offerId, 'The Stage API accepted the create command without returning an offer ID.');
        $this->waitForSuccessfulCommand($offers, $create->data->commandId);
        $created = $this->waitForOffer($offers, $offerId);

        return new StageCreatedOffer(
            $offerId,
            $create->data->commandId,
            $create->statusCode,
            $created,
            $externalId ?? '',
        );
    }

    final protected function closeStageOffer(OfferId $offerId): void
    {
        $offers = $this->stageOrganization()->offers();
        $close = $offers->close($offerId);
        $this->waitForSuccessfulCommand($offers, $close->data->commandId);
        $this->waitForOfferStatus($offers, $offerId, OfferStatus::CLOSED);
    }

    final protected function closeStageOfferQuietly(?OfferId $offerId): void
    {
        if (!$offerId instanceof OfferId) {
            return;
        }
        try {
            $this->closeStageOffer($offerId);
        } catch (Throwable) {
            // Cleanup must not hide the original assertion failure.
        }
    }

    final protected function waitForCommand(OffersResource $offers, CommandId $commandId): CommandDetails
    {
        $configuration = $this->stageConfig();
        $deadline = microtime(true) + $configuration->commandTimeoutSeconds;

        do {
            $command = $offers->command($commandId)->data;
            if (in_array($command->status->value, ['SUCCESS', 'FAILURE'], true)) {
                return $command;
            }
            $this->waitBeforeNextPoll($deadline);
        } while (microtime(true) < $deadline);

        self::fail('A Stage command did not finish before the configured timeout.');
    }

    final protected function waitForSuccessfulCommand(OffersResource $offers, CommandId $commandId): CommandDetails
    {
        $command = $this->waitForCommand($offers, $commandId);
        if ($command->status->value === 'FAILURE') {
            self::fail('A Stage offer command finished with FAILURE: ' . self::summarizeCommandErrors($command));
        }
        if ($command->errors !== []) {
            self::fail('A Stage offer command completed with validation errors: ' . self::summarizeCommandErrors($command));
        }

        return $command;
    }

    final protected function waitForOffer(OffersResource $offers, OfferId $offerId): OfferDetails
    {
        $deadline = microtime(true) + $this->stageConfig()->commandTimeoutSeconds;

        do {
            try {
                return $offers->get($offerId)->data;
            } catch (NotFoundException) {
                foreach ($offers->events(new OfferEventsOptions(limit: 100))->data as $event) {
                    if ($event->offerId->value === $offerId->value
                        && in_array($event->type->value, ['VALIDATION_FAILED', 'REJECTED'], true)
                    ) {
                        self::fail(sprintf(
                            'Stage asynchronously rejected the offer with %s. The offer event feed contains no field-level validation details.',
                            $event->type->value,
                        ));
                    }
                }
                $this->waitBeforeNextPoll($deadline);
            }
        } while (microtime(true) < $deadline);

        self::fail('The Stage offer was not readable before the configured timeout.');
    }

    final protected function waitForOfferStatus(OffersResource $offers, OfferId $offerId, string $expectedStatus): OfferDetails
    {
        return $this->waitForOfferMatching(
            $offers,
            $offerId,
            static fn (OfferDetails $offer): bool => $offer->status->value === $expectedStatus,
            'The Stage offer did not reach the expected lifecycle status before the configured timeout.',
        );
    }

    /**
     * @param callable(OfferDetails): bool $predicate
     */
    final protected function waitForOfferMatching(
        OffersResource $offers,
        OfferId $offerId,
        callable $predicate,
        string $failure,
    ): OfferDetails {
        $deadline = microtime(true) + $this->stageConfig()->commandTimeoutSeconds;

        do {
            $offer = $offers->get($offerId)->data;
            if ($predicate($offer)) {
                return $offer;
            }
            $this->waitBeforeNextPoll($deadline);
        } while (microtime(true) < $deadline);

        self::fail($failure);
    }

    final protected function waitBeforeNextPoll(float $deadline): void
    {
        $remainingMilliseconds = (int) max(0, ($deadline - microtime(true)) * 1000);
        $sleepMilliseconds = min($this->stageConfig()->pollIntervalMilliseconds, $remainingMilliseconds);
        if ($sleepMilliseconds > 0) {
            usleep($sleepMilliseconds * 1000);
        }
    }

    /** @return array<string, mixed> */
    final protected function stageProductHint(OffersResource $offers, string $ean): array
    {
        $hints = $offers->hints(new ProductHintOptions(
            ean: new Ean($ean),
            limit: 1,
            language: ResponseLanguage::POLISH,
        ))->data;
        if ($hints->items === []) {
            self::fail('The configured Stage product_ean has no product hint. Use a Stage catalogue EAN.');
        }
        $product = $hints->items[0]->product;
        $categoryId = $product['categoryId'] ?? null;
        if (!is_string($categoryId) || $categoryId !== $this->stageLeafCategoryId()->value) {
            self::fail('The configured Stage product hint belongs to a different category than leaf_category_id.');
        }

        return $product;
    }

    /** @param array<string, mixed> $product */
    final protected static function productString(array $product, string $field): string
    {
        $value = $product[$field] ?? null;
        if (!is_string($value) || $value === '') {
            self::fail(sprintf('The Stage product hint did not contain a non-empty %s.', $field));
        }

        return $value;
    }

    /** @param array<string, mixed> $product */
    final protected static function stageDescription(array $product): string
    {
        $description = trim(self::productString($product, 'description'));
        $suffix = ' Oferta testowa integracji Von Halsky SDK uruchamiana wyłącznie w środowisku Stage.';
        if (mb_strlen($description . $suffix) < 100) {
            $suffix .= ' Nie jest przeznaczona do rzeczywistej sprzedaży ani realizacji zamówień.';
        }

        $result = $description . $suffix;
        self::assertGreaterThanOrEqual(100, mb_strlen($result));

        return $result;
    }

    /** @return list<AttributeValue> */
    final protected function requiredCategoryAttributes(): array
    {
        $valuesByName = [
            'Bohater / Bajka' => 'serduszka',
            'Kolor' => 'wielokolorowy',
            'Płeć' => 'dziewczynka',
            'Typ' => 'miejski',
            'Wielkość' => 'duża (mieszcząca A4)',
        ];
        $definitions = $this->stageClient()->categories()->attributes(
            $this->stageLeafCategoryId(),
            ResponseLanguage::POLISH,
        )->data;

        $result = [];
        foreach ($definitions as $definition) {
            if ($definition->type->value !== 'TEXT_VALUE' || $definition->dictionary !== null) {
                continue;
            }
            if (!isset($valuesByName[$definition->name])) {
                continue;
            }
            $result[] = new AttributeValue(
                $definition->id,
                [$valuesByName[$definition->name]],
                ResponseLanguage::POLISH->value,
            );
        }

        self::assertCount(5, $result, 'The Stage Plecaki szkolne category no longer exposes the expected text attributes.');

        return $result;
    }

    final protected function offerPath(OfferId $offerId): string
    {
        return '/v1/organizations/' . rawurlencode($this->stageOrganizationId()->value)
            . '/offers/' . rawurlencode($offerId->value);
    }

    /**
     * @param array<string, mixed> $payload
     */
    final protected function rawOfferPatch(OfferId $offerId, array $payload): ResponseInterface
    {
        return $this->stageRequestExecutor()->executeJson(
            'PATCH',
            $this->offerPath($offerId),
            $payload,
            [],
            ['Content-Type' => 'application/merge-patch+json'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    final protected function rawOfferCreate(array $payload): ResponseInterface
    {
        return $this->stageRequestExecutor()->executeJson(
            'POST',
            '/v1/organizations/' . rawurlencode($this->stageOrganizationId()->value) . '/offers',
            $payload,
        );
    }

    final protected function assertExceptionHasNoSecret(Throwable $exception, string $secret): void
    {
        self::assertStringNotContainsString($secret, $exception->getMessage());
        $previous = $exception->getPrevious();
        while ($previous instanceof Throwable) {
            self::assertStringNotContainsString($secret, $previous->getMessage());
            $previous = $previous->getPrevious();
        }
    }

    final protected function assertProblemJson(ApiException $exception): void
    {
        self::assertArrayHasKey('Content-Type', $exception->safeHeaders);
        $contentType = implode(', ', $exception->safeHeaders['Content-Type']);
        self::assertMatchesRegularExpression('/application\\/problem\\+json|application\\/json/i', $contentType);
        self::assertNotSame('', $exception->getMessage());
    }

    final protected function expectLocalRejection(callable $factory): InvalidRequestException
    {
        try {
            $factory();
        } catch (InvalidRequestException $exception) {
            self::addToAssertionCount(1);

            return $exception;
        }

        self::fail('Expected the SDK to reject the value before network I/O.');
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

    private static function summarizeCommandErrors(CommandDetails $command): string
    {
        $parts = [];
        foreach ($command->errors as $error) {
            $parts[] = trim(($error->fieldName ?? 'field') . ': code-or-message-present');
        }

        return $parts === [] ? $command->status->value : implode('; ', $parts);
    }
}
