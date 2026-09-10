<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\OAuthScope;
use DevLancer\VonHalsky\Exception\AuthenticationException;
use DevLancer\VonHalsky\Exception\AuthenticationFlowException;
use DevLancer\VonHalsky\Exception\AuthorizationException;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use PHPUnit\Framework\Attributes\Group;

#[Group('stage')]
final class PhaseThreeOAuthBoundaryTest extends StageTestCase
{
    public function testClientCredentialsTokenHasRequestedMerchantScopes(): void
    {
        $tokens = $this->stageTokens();
        self::assertNotSame('', $tokens->accessToken->value);
        self::assertSame('bearer', strtolower($tokens->tokenType));
        if ($tokens->scopes !== []) {
            self::assertTrue(
                in_array(OAuthScope::OffersRead->value, $tokens->scopes, true)
                || in_array(OAuthScope::OffersWrite->value, $tokens->scopes, true),
            );
        }
        self::assertNull($tokens->refreshToken);
    }

    public function testAuthorizationCodePkceRequestShapeTargetsStageAuthorizeUrl(): void
    {
        $request = $this->stageOAuth()->createAuthorizationRequest(
            $this->stageConfig()->clientId,
            'https://127.0.0.1/sdk-stage-callback',
            [OAuthScope::OpenId, OAuthScope::OffersRead],
        );
        self::assertStringStartsWith('https://stage-account.inpost-group.com/oauth2/authorize?', $request->authorizationUrl);
        self::assertStringContainsString('code_challenge=', $request->authorizationUrl);
        self::assertStringContainsString('code_challenge_method=S256', $request->authorizationUrl);
        self::assertStringContainsString('state=', $request->authorizationUrl);
        self::assertNotSame('', $request->codeVerifier);
        self::assertNotSame('', $request->state);
    }

    public function testInvalidClientSecretIsRejectedWithoutLeakingTheSecret(): void
    {
        $secret = 'sdk-stage-invalid-client-secret';
        try {
            $this->stageOAuth()->requestClientCredentialsToken(
                $this->stageConfig()->clientId,
                $secret,
                [OAuthScope::OpenId],
            );
            self::fail('Client Credentials accepted an invalid client secret.');
        } catch (AuthenticationFlowException $exception) {
            $this->assertExceptionHasNoSecret($exception, $secret);
            $this->assertExceptionHasNoSecret($exception, $this->stageConfig()->clientSecret);
        }
    }

    public function testWriteWithoutOffersWriteScopeIsDenied(): void
    {
        $client = $this->createStageClient([
            OAuthScope::OpenId,
            OAuthScope::CategoriesRead,
            OAuthScope::OffersRead,
        ]);
        $createdId = null;
        try {
            $response = $client->forOrganization($this->stageOrganizationId())->offers()->create(
                $this->createSyntheticOfferRequestForScopeProbe(),
            );
            $createdId = $response->data->offerId;
            self::fail('An offer write succeeded without api:offers:write.');
        } catch (AuthorizationException | AuthenticationException $exception) {
            self::assertContains($exception->statusCode, [401, 403]);
            $this->assertProblemJson($exception);
        } finally {
            $this->closeStageOfferQuietly($createdId);
        }
    }

    public function testOrdersReadWithoutOrdersReadScopeIsDenied(): void
    {
        $client = $this->createStageClient([
            OAuthScope::OpenId,
            OAuthScope::CategoriesRead,
            OAuthScope::OffersRead,
        ]);
        try {
            $client->forOrganization($this->stageOrganizationId())->orders()->list(new OrderListOptions(limit: 1));
            self::fail('An order list succeeded without api:orders:read.');
        } catch (AuthorizationException | AuthenticationException $exception) {
            self::assertContains($exception->statusCode, [401, 403]);
            $this->assertProblemJson($exception);
        }
    }

    public function testInvalidAccessTokenReturnsAuthenticationError(): void
    {
        $token = 'invalid-stage-probe-token';
        $client = $this->clientWithAccessToken(new AccessToken($token, new DateTimeImmutable('+1 hour')));
        try {
            $client->categories()->list(new CategoryTreeOptions(depth: 1));
            self::fail('A catalogue read succeeded with an invalid access token.');
        } catch (AuthenticationException $exception) {
            self::assertSame(401, $exception->statusCode);
            $this->assertProblemJson($exception);
            $this->assertExceptionHasNoSecret($exception, $token);
        }
    }

    public function testStageTestsRefuseTheProductionEnvironmentId(): void
    {
        $environment = $this->stageEnvironment();
        self::assertSame('stage', $environment->id);
        self::assertStringContainsString('stage-api.', $environment->apiBaseUrl);
        self::assertStringNotContainsString('://api.inpost-group.com/', $environment->apiBaseUrl);
    }

    private function createSyntheticOfferRequestForScopeProbe(): CreateOfferRequest
    {
        $configuration = $this->stageConfig();
        $product = $this->stageProductHint($this->stageOrganization()->offers(), $configuration->productEan);

        return new CreateOfferRequest(
            new ProductProposal(
                self::productString($product, 'name'),
                self::stageDescription($product),
                self::productString($product, 'brand'),
                $this->stageLeafCategoryId(),
                new Ean($configuration->productEan),
                attributes: $this->requiredCategoryAttributes(),
            ),
            new Stock(1),
            new Price(Money::fromDecimal('9.99'), '23%'),
            GpsrInfo::notRequired(),
            $this->uniqueExternalId('-scope'),
            1,
            [new OfferImage('sdk-stage-offer.png', $configuration->offerImageUrl, 1)],
        );
    }
}
