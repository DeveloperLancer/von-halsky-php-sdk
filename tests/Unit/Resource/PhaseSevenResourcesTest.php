<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Resource;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Http\HttpClientDependencies;
use DevLancer\VonHalsky\Model\Order\RefundRequest;
use DevLancer\VonHalsky\Model\PostSale\ResolutionDescription;
use DevLancer\VonHalsky\Request\ClaimListOptions;
use DevLancer\VonHalsky\Request\OrderEventsOptions;
use DevLancer\VonHalsky\Request\OrderListOptions;
use DevLancer\VonHalsky\Request\ReturnListOptions;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\ValueObject\ClaimId;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\EventId;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OrderId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\ValueObject\ReturnId;
use DevLancer\VonHalsky\ValueObject\UtcDateTime;
use DevLancer\VonHalsky\VonHalskyClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class PhaseSevenResourcesTest extends TestCase
{
    public function testAllEighteenCurrentOperationsUseContractRoutesAndTypedMoney(): void
    {
        $order = $this->order();
        $claim = $this->claim();
        $return = $this->return();
        [$sdk, $http] = $this->client(
            $this->json(['data' => [['id' => 'claim-type-1', 'description' => 'Damaged']]]),
            $this->json(['items' => [$claim], 'page' => $this->page()]),
            $this->json(['data' => [$order], 'page' => $this->page()]),
            $this->json(['commandId' => 'command-1', 'status' => 'SUCCESS']),
            $this->json(['data' => [['id' => 'event-1', 'orderEventType' => 'CREATED', 'order' => ['id' => 'order-1'], 'occurredAt' => '2026-08-04T20:00:00Z']]]),
            $this->json($order),
            $this->json(['commandId' => 'command-accept', 'status' => 'PENDING'], 202),
            $this->json($claim),
            $this->json(['message' => 'Partial refund accepted']),
            $this->json(['message' => 'Refund accepted']),
            $this->json(['message' => 'Claim rejected']),
            $this->json(['refundAmount' => ['amount' => 12.34, 'currency' => 'PLN'], 'status' => 'PENDING']),
            $this->json(['data' => [$return], 'page' => $this->page()]),
            $this->json(['data' => [$return], 'page' => $this->page()]),
            $this->json($return),
            $this->json(['message' => 'Return accepted']),
            $this->json(['message' => 'Return rejected']),
            $this->json([['code' => 'APM', 'name' => 'Parcel locker']]),
        );

        $organization = $sdk->forOrganization(OrganizationId::fromString('organization/1'));
        $orderId = OrderId::fromString('order/1');
        $claimId = ClaimId::fromString('claim/1');
        $returnId = ReturnId::fromString('return/1');
        $instant = UtcDateTime::fromString('2026-08-04T20:00:00Z');

        self::assertSame('claim-type-1', $sdk->claims()->types()->data[0]->id->value);
        self::assertSame('claim-1', $organization->claims()->list(new ClaimListOptions(states: ['APPROVED'], submissionDateFrom: $instant, sort: ['-submission_date']))->data->items[0]->id->value);
        self::assertSame('49.99', $organization->orders()->list(new OrderListOptions(paymentStatuses: ['PAID', 'NOT_PAID'], updatedAtGte: $instant))->data->items[0]->finalPrice->amount);
        self::assertSame('SUCCESS', $organization->orders()->command(CommandId::fromString('command/1'))->data->status->value);
        self::assertSame('event-1', $organization->orders()->events(new OrderEventsOptions(EventId::fromString('event/0')))->data[0]->id->value);
        self::assertSame('order-1', $organization->orders()->get($orderId)->data->id->value);
        self::assertSame('command-accept', $organization->orders()->accept($orderId)->data->commandId->value);
        self::assertSame('claim-1', $organization->claims()->get($orderId, $claimId)->data->id->value);
        self::assertSame('Partial refund accepted', $organization->claims()->partialRefund($orderId, $claimId, new ResolutionDescription('Partial resolution'))->data->message);
        self::assertSame('Refund accepted', $organization->claims()->refund($orderId, $claimId)->data->message);
        self::assertSame('Claim rejected', $organization->claims()->reject($orderId, $claimId)->data->message);
        self::assertSame(1234, $organization->orders()->refund($orderId, new RefundRequest(Money::fromDecimal('12.34')))->data->amount?->minorUnits());
        self::assertSame('return-1', $organization->returns()->forOrder($orderId, new ReturnListOptions(['ACCEPTED']))->data->items[0]->id->value);
        self::assertSame('return-1', $organization->returns()->list()->data->items[0]->id->value);
        self::assertSame('return-1', $organization->returns()->get($returnId)->data->id->value);
        self::assertSame('Return accepted', $organization->returns()->accept($returnId)->data->message);
        self::assertSame('Return rejected', $organization->returns()->reject($returnId)->data->message);
        self::assertSame('Parcel locker', $sdk->orders()->deliveryMethods()->data[0]->name);

        self::assertCount(18, $http->requests());
        self::assertStringContainsString('paymentStatus%5B0%5D=PAID', (string) $http->requestAt(2)->getUri());
        self::assertStringContainsString('updatedAtGte=2026-08-04T20%3A00%3A00%2B00%3A00', (string) $http->requestAt(2)->getUri());
        self::assertSame('/inpsa/v1/organizations/organization%2F1/orders/order%2F1/refund', $http->requestAt(11)->getUri()->getPath());
        self::assertSame(['amount' => ['amount' => 12.34, 'currency' => 'PLN']], json_decode((string) $http->requestAt(11)->getBody(), true, 512, JSON_THROW_ON_ERROR));
        self::assertSame('/inpsa/v2/orders/delivery-methods', $http->requestAt(17)->getUri()->getPath());
    }

    /** @return array<string, mixed> */
    private function order(): array
    {
        return [
            'id' => 'order-1', 'organizationId' => 'organization-1', 'status' => 'CREATED',
            'finalPrice' => ['amount' => 49.99, 'currency' => 'PLN'],
            'basePrice' => ['amount' => 59.99, 'currency' => 'PLN'],
            'orderLines' => [['id' => 'line-1', 'quantity' => 2]],
            'customer' => ['email' => 'opaque-hashmail@example.invalid'],
            'delivery' => ['deliveryType' => 'APM', 'email' => 'shipx@example.invalid', 'parcels' => []],
            'invoice' => ['email' => 'invoice@example.invalid', 'legalForm' => 'PERSON'],
            'paymentDetails' => ['status' => 'PAID'],
            'createdAt' => '2026-08-04T20:00:00Z', 'updatedAt' => '2026-08-04T20:01:00Z',
        ];
    }

    /** @return array<string, mixed> */
    private function claim(): array
    {
        return [
            'claimId' => 'claim-1', 'state' => 'APPROVED', 'resolution' => 'PARTIAL_REFUND',
            'claimant' => ['email' => 'redacted@example.invalid'], 'relatedOrder' => ['id' => 'order-1'],
            'orderLines' => [['id' => 'line-1', 'amount' => ['amount' => 12.34, 'currency' => 'PLN']]],
            'createdAt' => '2026-08-04T20:00:00Z', 'expiresAt' => '2026-08-11T20:00:00Z',
        ];
    }

    /** @return array<string, mixed> */
    private function return(): array
    {
        return [
            'id' => 'return-1', 'orderId' => 'order-1', 'status' => 'ACCEPTED',
            'client' => ['email' => 'redacted@example.invalid'], 'orderLines' => [['id' => 'line-1', 'quantity' => 2]],
            'createdAt' => '2026-08-04T20:00:00Z',
        ];
    }

    /** @return array{offset: int, limit: int, total: int} */
    private function page(): array
    {
        return ['offset' => 0, 'limit' => 10, 'total' => 1];
    }

    private function json(mixed $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data, JSON_THROW_ON_ERROR));
    }

    /** @return array{VonHalskyClient, FakeHttpClient} */
    private function client(Response ...$responses): array
    {
        $http = new FakeHttpClient(array_values($responses));
        $factory = new Psr17Factory();

        return [
            new VonHalskyClient(
                Environment::stage(),
                new StaticTokenProvider(new AccessToken('token', new DateTimeImmutable('2030-01-01T00:00:00Z'))),
                new HttpClientDependencies($http, $factory, $factory),
            ),
            $http,
        ];
    }
}
