<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('stage')]
final class PhaseSevenReadOnlySmokeTest extends TestCase
{
    public function testOrderAndPostSaleReadsOnStageWithoutExposingPayloads(): void
    {
        $token = $this->requiredEnvironment('VON_HALSKY_STAGE_ACCESS_TOKEN');
        $organizationId = OrganizationId::fromString($this->requiredEnvironment('VON_HALSKY_STAGE_ORGANIZATION_ID'));
        $client = VonHalskyClient::create(
            new StaticTokenProvider(new AccessToken($token, new DateTimeImmutable('2099-01-01T00:00:00Z'))),
            Environment::stage(),
        );
        $organization = $client->forOrganization($organizationId);

        self::assertSame(200, $client->orders()->deliveryMethods()->statusCode);
        self::assertSame(200, $client->claims()->types()->statusCode);
        self::assertSame(200, $organization->orders()->list()->statusCode);
        self::assertSame(200, $organization->returns()->list()->statusCode);
        self::assertSame(200, $organization->claims()->list()->statusCode);
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
