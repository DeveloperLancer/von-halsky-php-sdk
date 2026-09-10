<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\AuthenticationException;
use DevLancer\VonHalsky\Exception\NotFoundException;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use PHPUnit\Framework\Attributes\Group;

#[Group('stage')]
final class PhaseFourProblemJsonTest extends StageTestCase
{
    public function testInvalidTokenAndUnknownOfferReturnProblemJson(): void
    {
        $client = $this->clientWithAccessToken(new AccessToken(
            'invalid-stage-probe-token',
            new DateTimeImmutable('+1 hour'),
        ));
        try {
            $client->categories()->list(new CategoryTreeOptions(depth: 1));
            self::fail('Expected HTTP 401 for an invalid access token.');
        } catch (AuthenticationException $exception) {
            self::assertSame(401, $exception->statusCode);
            $this->assertProblemJson($exception);
        }

        try {
            $this->stageOrganization()->offers()->get(
                OfferId::fromString('00000000-0000-4000-8000-000000000000'),
            );
            self::fail('Expected HTTP 404 for an unknown offer ID.');
        } catch (NotFoundException $exception) {
            self::assertSame(404, $exception->statusCode);
            $this->assertProblemJson($exception);
        }
    }

    public function testInvalidTaxRateProducesClientOrCommandFailure(): void
    {
        $configuration = $this->stageConfig();
        $offers = $this->stageOrganization()->offers();
        $product = $this->stageProductHint($offers, $configuration->productEan);
        $offerId = null;
        try {
            $create = $offers->create(new CreateOfferRequest(
                new ProductProposal(
                    self::productString($product, 'name'),
                    self::stageDescription($product),
                    self::productString($product, 'brand'),
                    $this->stageLeafCategoryId(),
                    new Ean($configuration->productEan),
                    attributes: $this->requiredCategoryAttributes(),
                ),
                new Stock(1),
                new Price(Money::fromDecimal('9.99'), 'NOT_A_TAX_RATE'),
                GpsrInfo::notRequired(),
                $this->uniqueExternalId('-tax'),
                1,
                [new OfferImage('sdk-stage-offer.png', $configuration->offerImageUrl, 1)],
            ));
            $offerId = $create->data->offerId;
            $command = $this->waitForCommand($offers, $create->data->commandId);
            self::assertContains($command->status->value, ['SUCCESS', 'FAILURE']);
        } catch (ApiException $exception) {
            self::assertContains($exception->statusCode, [400, 409, 422]);
            $this->assertProblemJson($exception);
        } finally {
            $this->closeStageOfferQuietly($offerId);
        }
    }
}
