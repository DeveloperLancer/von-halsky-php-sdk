<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Resource;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Http\HttpClientDependencies;
use DevLancer\VonHalsky\Model\Attachment\AttachmentPriority;
use DevLancer\VonHalsky\Model\Attachment\AttachmentType;
use DevLancer\VonHalsky\Model\Offer\BatchCreateOffersRequest;
use DevLancer\VonHalsky\Model\Offer\CreateOfferRequest;
use DevLancer\VonHalsky\Model\Offer\GpsrInfo;
use DevLancer\VonHalsky\Model\Offer\OfferAttributesPatch;
use DevLancer\VonHalsky\Model\Offer\OfferImage;
use DevLancer\VonHalsky\Model\Offer\OfferPriceUpdate;
use DevLancer\VonHalsky\Model\Offer\OfferStockUpdate;
use DevLancer\VonHalsky\Model\Offer\PatchOfferRequest;
use DevLancer\VonHalsky\Model\Offer\Price;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Model\Offer\RemoveAttribute;
use DevLancer\VonHalsky\Model\Offer\Stock;
use DevLancer\VonHalsky\Model\Offer\UpsertAttribute;
use DevLancer\VonHalsky\Model\OptionalValue;
use DevLancer\VonHalsky\Request\ProductHintOptions;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\Tests\Support\NonSeekableStream;
use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\CommandId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\Money;
use DevLancer\VonHalsky\ValueObject\OfferId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;

final class PhaseSixResourcesTest extends TestCase
{
    public function testAllNineteenOperationsUseTheContractRoutesAndTypedResults(): void
    {
        $offer = $this->offerDetails();
        [$sdk, $http] = $this->client(
            $this->json(['data' => [['name' => 'Bottle', 'depositType' => ['id' => 'deposit-1', 'price' => ['amount' => 0.5, 'currency' => 'PLN']]]]]),
            $this->json(['data' => [$offer], 'page' => $this->page()]),
            $this->json(['commandId' => 'command-create', 'offerId' => 'offer-1'], 201),
            $this->json([['commandId' => 'command-batch', 'offerId' => 'offer-2']], 201),
            $this->json(['commandId' => 'command-create', 'status' => 'FAILURE', 'errors' => [['message' => 'Invalid value', 'fieldName' => 'product.name']]]),
            $this->json(['data' => [['id' => 'event-1', 'offerEventType' => 'VALIDATION_FAILED', 'offer' => ['id' => 'offer-1'], 'occurredAt' => '2026-08-04T20:00:00Z']]]),
            $this->json(['data' => [['product' => ['productId' => 'product-1', 'name' => 'Product'], 'gpsr' => []]], 'page' => $this->page()]),
            $this->json([['commandId' => 'price-command', 'offerId' => 'offer-1', 'status' => 'PENDING']]),
            $this->json([['commandId' => 'stock-command', 'offerId' => 'offer-1', 'status' => 'PENDING']]),
            $this->json($offer),
            $this->json($offer),
            $this->json(['data' => [['id' => 'attachment-1', 'name' => 'manual.pdf', 'attachmentType' => 'MANUAL', 'createdAt' => '2026-08-04T20:00:00Z']], 'page' => $this->page()]),
            $this->json(['commandId' => 'upload-command', 'status' => 'PENDING'], 202),
            new Response(200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="manual.pdf"'], 'streamed-file'),
            new Response(204),
            $this->json(['commandId' => 'attributes-command', 'offerId' => 'offer-1', 'status' => 'PENDING']),
            $this->json(['commandId' => 'close-command', 'status' => 'PENDING'], 202),
            $this->json(['commandId' => 'reopen-command', 'status' => 'PENDING'], 202),
            $this->json(['commandId' => 'priority-command', 'status' => 'PENDING'], 202),
        );

        $organization = $sdk->forOrganization(OrganizationId::fromString('organization/1'));
        $offers = $organization->offers();
        $attachments = $organization->attachments();
        $offerId = OfferId::fromString('offer/1');
        $create = $this->createRequest();

        self::assertSame('deposit-1', $sdk->offers()->depositTypes()->data[0]->id);
        self::assertSame('offer-1', $offers->list()->data->items[0]->id->value);
        self::assertSame('command-create', $offers->create($create)->data->commandId->value);
        self::assertSame('offer-2', $offers->createBatch(new BatchCreateOffersRequest([$create]))->data[0]->offerId?->value);
        self::assertSame('Invalid value', $offers->command(CommandId::fromString('command-create'))->data->errors[0]->message);
        self::assertSame('VALIDATION_FAILED', $offers->events()->data[0]->type->value);
        self::assertSame('product-1', $offers->hints(new ProductHintOptions(ean: new Ean('5901234123457')))->data->items[0]->product['productId']);
        self::assertSame('price-command', $offers->updatePrices([new OfferPriceUpdate($offerId, Money::fromDecimal('12.50'))])->data[0]->commandId->value);
        self::assertSame('stock-command', $offers->updateStocks([new OfferStockUpdate($offerId, new Stock(5))])->data[0]->commandId->value);
        self::assertSame('offer-1', $offers->get($offerId)->data->id->value);
        $offers->patch($offerId, new PatchOfferRequest(
            price: OptionalValue::of(new Price(Money::fromDecimal('19.99'), '23%')),
            stock: OptionalValue::of(new Stock(2)),
            images: OptionalValue::of([new OfferImage('image.png', 'https://example.com/image.png', 1)]),
        ));
        self::assertSame('attachment-1', $attachments->list($offerId)->data->items[0]->id->value);

        $factory = new Psr17Factory();
        $upload = new NonSeekableStream($factory->createStream('large-stream-placeholder'));
        self::assertSame('upload-command', $attachments->upload($offerId, AttachmentType::MANUAL, 'manual.pdf', 'application/pdf', $upload)->data->commandId->value);
        $download = $attachments->download($offerId, AttachmentId::fromString('attachment/1'))->data;
        self::assertSame('manual.pdf', $download->filename);
        self::assertSame('streamed-file', $download->stream->getContents());
        self::assertSame(204, $attachments->delete($offerId, AttachmentId::fromString('attachment/1'))->statusCode);
        self::assertSame('attributes-command', $offers->updateAttributes($offerId, new OfferAttributesPatch([new UpsertAttribute('attribute-1', ['red']), new RemoveAttribute('attribute-2')]))->data->commandId->value);
        self::assertSame('close-command', $offers->close($offerId)->data->commandId->value);
        self::assertSame('reopen-command', $offers->reopen($offerId)->data->commandId->value);
        self::assertSame('priority-command', $attachments->updatePriorities($offerId, [
            new AttachmentPriority(AttachmentId::fromString('attachment/1'), 1),
        ])->data->commandId->value);

        self::assertCount(19, $http->requests());
        self::assertSame('https://stage-api.inpost-group.com/inpsa/v1/organizations/organization%2F1/offers/offer%2F1/attachments?attachmentType=MANUAL', (string) $http->requestAt(12)->getUri());
        self::assertStringContainsString('name="file"; filename="manual.pdf"', (string) $http->requestAt(12)->getBody());
        self::assertSame('application/merge-patch+json', $http->requestAt(10)->getHeaderLine('Content-Type'));
        self::assertSame([
            'price' => ['grossPrice' => ['amount' => 19.99, 'currency' => 'PLN'], 'taxRateInfo' => '23%'],
            'stock' => ['quantity' => 2, 'unit' => 'UNIT'],
            'images' => [['fileName' => 'image.png', 'fileUrl' => 'https://example.com/image.png', 'priority' => 1]],
        ], json_decode((string) $http->requestAt(10)->getBody(), true, 512, JSON_THROW_ON_ERROR));
        self::assertSame(
            'https://stage-api.inpost-group.com/inpsa/v1/organizations/organization%2F1/offers/offer%2F1/attachments/priority',
            (string) $http->requestAt(18)->getUri(),
        );
        self::assertSame(
            [['attachmentId' => 'attachment/1', 'priority' => 1]],
            json_decode((string) $http->requestAt(18)->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testBatchAndOfferValidationBoundaries(): void
    {
        $request = $this->createRequest();
        new BatchCreateOffersRequest(array_fill(0, 500, $request));
        self::addToAssertionCount(1);

        $this->expectException(InvalidRequestException::class);
        new BatchCreateOffersRequest(array_fill(0, 501, $request));
    }

    public function testInvalidRequestInputDoesNotReachTheHttpClient(): void
    {
        [$sdk, $http] = $this->client();
        $offers = $sdk->forOrganization(OrganizationId::fromString('organization-1'))->offers();

        try {
            self::invokeWithInvalidArguments($offers, 'updatePrices', [['not-an-update']]);
            self::fail('Expected malformed price updates to be rejected.');
        } catch (InvalidRequestException $exception) {
            self::assertSame('offers.prices[0]', $exception->fieldPath);
        }

        self::assertCount(0, $http->requests());
    }

    public function testAttachmentMimeTypeMustBeAllowedForItsType(): void
    {
        [$sdk, $http] = $this->client();
        $attachments = $sdk->forOrganization(OrganizationId::fromString('organization-1'))->attachments();
        $factory = new Psr17Factory();

        try {
            $attachments->upload(
                OfferId::fromString('offer-1'),
                AttachmentType::IMAGE,
                'image.png',
                'application/pdf',
                $factory->createStream('file'),
            );
            self::fail('Expected an invalid attachment MIME type.');
        } catch (InvalidRequestException $exception) {
            self::assertSame('attachment.mimeType', $exception->fieldPath);
        }

        self::assertCount(0, $http->requests());
        self::assertTrue(AttachmentType::OTHER->allowsMimeType('video/mp4'));
        self::assertFalse(AttachmentType::MANUAL->allowsMimeType('image/png'));

        [$validSdk, $validHttp] = $this->client($this->json(['commandId' => 'gif-command'], 202));
        $command = $validSdk->forOrganization(OrganizationId::fromString('organization-1'))->attachments()->upload(
            OfferId::fromString('offer-1'),
            AttachmentType::IMAGE,
            'animation.gif',
            'image/gif',
            $factory->createStream('gif'),
        )->data;
        self::assertSame('gif-command', $command->commandId->value);
        self::assertCount(1, $validHttp->requests());
    }

    public function testEmptyAttributePatchIsAValidNoOpRequest(): void
    {
        [$sdk, $http] = $this->client($this->json([
            'commandId' => 'empty-attributes-command',
            'offerId' => 'offer-1',
            'status' => 'PENDING',
        ]));

        $command = $sdk->forOrganization(OrganizationId::fromString('organization-1'))->offers()->updateAttributes(
            OfferId::fromString('offer-1'),
            new OfferAttributesPatch([]),
        )->data;

        self::assertSame('empty-attributes-command', $command->commandId->value);
        self::assertSame(
            ['operations' => []],
            json_decode((string) $http->requestAt(0)->getBody(), true, 512, JSON_THROW_ON_ERROR),
        );
    }

    public function testAttachmentPriorityRangeFollowsProductionContract(): void
    {
        new AttachmentPriority(AttachmentId::fromString('attachment-1'), 1);
        new AttachmentPriority(AttachmentId::fromString('attachment-1'), 1000);
        self::addToAssertionCount(2);

        foreach ([0, 1001] as $invalid) {
            try {
                new AttachmentPriority(AttachmentId::fromString('attachment-1'), $invalid);
                self::fail('Expected an invalid attachment priority.');
            } catch (InvalidRequestException $exception) {
                self::assertSame('attachment.priority', $exception->fieldPath);
            }
        }
    }

    private function createRequest(): CreateOfferRequest
    {
        return new CreateOfferRequest(
            new ProductProposal('Product', str_repeat('Description ', 10), 'Brand', CategoryId::fromString('leaf-1'), new Ean('5901234123457')),
            new Stock(10),
            new Price(Money::fromDecimal('49.99'), '23%'),
            GpsrInfo::notRequired(),
            'external-1',
            2,
            [new OfferImage('image.png', 'https://example.com/image.png', 1)],
        );
    }

    /** @return array<string, mixed> */
    private function offerDetails(): array
    {
        return [
            'offer' => [
                'id' => 'offer-1',
                'status' => 'FUTURE_STATUS',
                'product' => ['name' => 'Product'],
                'stock' => ['quantity' => 10, 'unit' => 'UNIT'],
                'price' => ['grossPrice' => ['amount' => 49.99, 'currency' => 'PLN']],
                'createdAt' => '2026-08-04T20:00:00Z',
            ],
            'metadata' => ['validationErrors' => []],
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

    /** @param array<int, mixed> $arguments */
    private static function invokeWithInvalidArguments(object $object, string $method, array $arguments): mixed
    {
        return (new \ReflectionMethod($object, $method))->invokeArgs($object, $arguments);
    }
}
