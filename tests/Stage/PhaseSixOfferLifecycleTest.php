<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('stage')]
final class PhaseSixOfferLifecycleTest extends TestCase
{
    public function testCreateObserveUpdateAndCloseOnStageWithExplicitWriteOptIn(): void
    {
        if (getenv('VON_HALSKY_STAGE_ALLOW_WRITES') !== '1') {
            self::markTestSkipped('Set VON_HALSKY_STAGE_ALLOW_WRITES=1 to permit destructive Stage writes.');
        }
        $token = $this->requiredEnvironment('VON_HALSKY_STAGE_ACCESS_TOKEN');
        $organizationId = OrganizationId::fromString($this->requiredEnvironment('VON_HALSKY_STAGE_ORGANIZATION_ID'));
        $categoryId = CategoryId::fromString($this->requiredEnvironment('VON_HALSKY_STAGE_LEAF_CATEGORY_ID'));
        $ean = new Ean($this->requiredEnvironment('VON_HALSKY_STAGE_PRODUCT_EAN'));
        $client = VonHalskyClient::create(
            new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('2099-01-01T00:00:00Z'))),
            Environment::stage(),
        );
        $offers = $client->forOrganization($organizationId)->offers();
        $offerId = null;

        try {
            $handle = $offers->create(new CreateOfferRequest(
                new ProductProposal('SDK Stage verification', 'Temporary offer created by the SDK Stage suite.', 'SDK Test', $categoryId, $ean),
                new Stock(1),
                new Price(Money::fromDecimal('9.99'), '23%'),
                GpsrInfo::notRequired(),
                'sdk-stage-' . bin2hex(random_bytes(8)),
                1,
            ))->data;
            $offerId = $handle->offerId;
            self::assertNotNull($offerId);
            self::assertNotSame('', $offers->command($handle->commandId)->data->status->value);
            $offers->events();
            self::addToAssertionCount(1);
            self::assertSame($offerId->value, $offers->get($offerId)->data->id->value);
            self::assertNotEmpty($offers->updatePrices([new OfferPriceUpdate($offerId, Money::fromDecimal('10.99'))])->data);
            self::assertNotEmpty($offers->updateStocks([new OfferStockUpdate($offerId, new Stock(2))])->data);
        } finally {
            if ($offerId instanceof OfferId) {
                $offers->close($offerId);
            }
        }
    }

    private function requiredEnvironment(string $name): string
    {
        $value = getenv($name);
        if (!is_string($value) || $value === '') {
            self::markTestSkipped($name . ' is not configured.');
        }

        return $value;
    }
}
