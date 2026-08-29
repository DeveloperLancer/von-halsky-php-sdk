<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Resource;

use DateTimeImmutable;
use DevLancer\VonHalsky\Auth\AccessToken;
use DevLancer\VonHalsky\Auth\StaticTokenProvider;
use DevLancer\VonHalsky\Environment\Environment;
use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Exception\AuthenticationException;
use DevLancer\VonHalsky\Exception\AuthorizationException;
use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Exception\NotFoundException;
use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\HttpClientDependencies;
use DevLancer\VonHalsky\Model\Category\AttributeType;
use DevLancer\VonHalsky\Model\Offer\AttributeValue;
use DevLancer\VonHalsky\Model\Offer\ProductProposal;
use DevLancer\VonHalsky\Request\CategoryDetailsOptions;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\Request\OrganizationListOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Tests\Support\FakeHttpClient;
use DevLancer\VonHalsky\ValueObject\CategoryId;
use DevLancer\VonHalsky\ValueObject\Ean;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use DevLancer\VonHalsky\VonHalskyClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PhaseFiveResourcesTest extends TestCase
{
    public function testListsOrganizationsWithMetadataAndTypedDto(): void
    {
        [$sdk, $http] = $this->client($this->jsonResponse([
            [
                'id' => 'organization-1',
                'name' => 'Shop',
                'status' => 'ACTIVE',
                'type' => 'SHOP',
                'parent' => ['id' => 'parent-1', 'name' => 'Brand', 'future' => true],
                'future' => 'retained',
            ],
        ], 200, [
            'X-RateLimit-Limit' => '100',
            'X-RateLimit-Remaining' => '99',
            'X-RateLimit-Reset' => '1893456000',
            'X-Correlation-ID' => 'correlation-1',
        ]));

        $result = $sdk->organizations()->list(new OrganizationListOptions(ResponseLanguage::POLISH));

        self::assertSame('organization-1', $result->data[0]->id?->value);
        self::assertSame('Brand', $result->data[0]->parent?->name);
        self::assertSame(['future' => 'retained'], $result->data[0]->additionalData());
        self::assertSame(['future' => true], $result->data[0]->parent->additionalData());
        self::assertSame(100, $result->rateLimit?->limit);
        self::assertSame('correlation-1', $result->headers->line('x-correlation-id'));
        self::assertSame('pl', $http->requestAt(0)->getHeaderLine('Accept-Language'));
    }

    public function testBrowsesTreeWithoutHiddenRequestsAndSupportsFiveLevels(): void
    {
        $tree = $this->categoryNode(0, 5);
        [$sdk, $http] = $this->client($this->jsonResponse([$tree]));

        $result = $sdk->categories()->list(new CategoryTreeOptions(
            4,
            CategoryId::fromString('root category'),
            ResponseLanguage::ENGLISH,
        ));

        $node = $result->data[0];
        for ($level = 0; $level < 5; ++$level) {
            self::assertSame('category-' . $level, $node->id->value);
            if ($level < 4) {
                $node = $node->children[0];
            }
        }
        self::assertCount(1, $http->requests());
        self::assertSame(
            'https://stage-api.inpost-group.com/inpsa/v1/categories?depth=4&categoryId=root%20category',
            (string) $http->requestAt(0)->getUri(),
        );
        self::assertSame('en', $http->requestAt(0)->getHeaderLine('Accept-Language'));
    }

    public function testGetsDetailedCategoryAndValidatesLeaf(): void
    {
        [$sdk] = $this->client(
            $this->jsonResponse([
                'id' => 'leaf-1',
                'name' => 'Leaf',
                'leaf' => true,
                'doesNotRequireGpsrInfo' => false,
                'description' => 'A leaf category',
                'relations' => [['id' => 'parent-1', 'relation' => 'MAIN_PARENT']],
                'metadata' => ['last-updated' => '2026-01-01T00:00:00Z'],
            ]),
            $this->jsonResponse([
                'id' => 'parent-1',
                'name' => 'Parent',
                'leaf' => false,
                'doesNotRequireGpsrInfo' => true,
                'description' => 'Not a leaf',
            ]),
        );

        $leaf = $sdk->categories()->get(
            CategoryId::fromString('leaf/1'),
            new CategoryDetailsOptions(0),
        )->data;
        self::assertSame($leaf, $leaf->requireLeaf());
        self::assertSame('parent-1', $leaf->relations[0]->categoryId?->value);

        $this->expectException(InvalidRequestException::class);
        $sdk->categories()->get(CategoryId::fromString('parent-1'))->data->requireLeaf();
    }

    public function testHydratesDictionariesAndKeepsUnknownAttributeType(): void
    {
        [$sdk] = $this->client($this->jsonResponse([
            [
                'id' => 'attribute-1',
                'name' => 'Future attribute',
                'type' => 'FUTURE_TYPE',
                'expectedValue' => 'ONE',
                'dictionary' => [
                    'id' => 'dictionary-1',
                    'name' => 'Values',
                    'options' => [
                        ['id' => 'option-1', 'value' => 'First', 'active' => true, 'lang' => 'en'],
                    ],
                ],
            ],
        ]));

        $attribute = $sdk->categories()->attributes(CategoryId::fromString('leaf-1'))->data[0];

        self::assertSame('FUTURE_TYPE', $attribute->type->value);
        self::assertFalse($attribute->type->isKnown());
        self::assertSame('First', $attribute->dictionary?->options[0]->value);
    }

    public function testBuildsProductValidatorFromOneAttributeRequest(): void
    {
        [$sdk, $http] = $this->client($this->jsonResponse([[
            'id' => 'attribute-1',
            'name' => 'Required attribute',
            'type' => 'TEXT_VALUE',
            'expectedValue' => 'ONE',
        ]], 200, [
            'X-Correlation-ID' => 'validator-correlation',
        ]));

        $response = $sdk->categories()->productValidator(
            CategoryId::fromString('leaf/1'),
            ResponseLanguage::POLISH,
        );
        $result = $response->data->validate(new ProductProposal(
            'Product name',
            str_repeat('Long product description. ', 5),
            'Brand',
            CategoryId::fromString('leaf/1'),
            new Ean('5901234123457'),
            attributes: [new AttributeValue('attribute-1', ['value'], 'pl')],
        ));

        self::assertTrue($result->isValid());
        self::assertCount(1, $http->requests());
        self::assertSame(
            'https://stage-api.inpost-group.com/inpsa/v1/categories/leaf%2F1/attributes',
            (string) $http->requestAt(0)->getUri(),
        );
        self::assertSame('pl', $http->requestAt(0)->getHeaderLine('Accept-Language'));
        self::assertSame(200, $response->statusCode);
        self::assertSame('validator-correlation', $response->correlationId);
    }

    /** @param class-string<ApiException> $exception */
    #[DataProvider('endpointErrorProvider')]
    public function testEveryEndpointMapsApiErrors(string $endpoint, int $status, string $exception): void
    {
        [$sdk] = $this->client($this->problemResponse($status));

        $this->expectException($exception);
        match ($endpoint) {
            'organizations' => $sdk->organizations()->list(),
            'categories' => $sdk->categories()->list(),
            'category' => $sdk->categories()->get(CategoryId::fromString('category-1')),
            'attributes' => $sdk->categories()->attributes(CategoryId::fromString('category-1')),
            'validator' => $sdk->categories()->productValidator(CategoryId::fromString('category-1')),
            default => throw new \LogicException('Unknown endpoint fixture.'),
        };
    }

    /** @return iterable<string, array{string, int, class-string<ApiException>}> */
    public static function endpointErrorProvider(): iterable
    {
        foreach (['organizations', 'categories', 'category', 'attributes', 'validator'] as $endpoint) {
            yield $endpoint . ' 401' => [$endpoint, 401, AuthenticationException::class];
            yield $endpoint . ' 403' => [$endpoint, 403, AuthorizationException::class];
            yield $endpoint . ' 404' => [$endpoint, 404, NotFoundException::class];
        }
    }

    /** @param 'organizations'|'categories'|'category'|'attributes'|'validator' $endpoint */
    #[DataProvider('endpointProvider')]
    public function testEveryEndpointRejectsMalformedResponse(string $endpoint): void
    {
        [$sdk] = $this->client(new Response(200, ['Content-Type' => 'application/json'], '{invalid'));

        $this->expectException(ResponseMappingException::class);
        match ($endpoint) {
            'organizations' => $sdk->organizations()->list(),
            'categories' => $sdk->categories()->list(),
            'category' => $sdk->categories()->get(CategoryId::fromString('category-1')),
            'attributes' => $sdk->categories()->attributes(CategoryId::fromString('category-1')),
            'validator' => $sdk->categories()->productValidator(CategoryId::fromString('category-1')),
        };
    }

    /** @return iterable<string, array{'organizations'|'categories'|'category'|'attributes'|'validator'}> */
    public static function endpointProvider(): iterable
    {
        yield 'organizations' => ['organizations'];
        yield 'categories' => ['categories'];
        yield 'category' => ['category'];
        yield 'attributes' => ['attributes'];
        yield 'validator' => ['validator'];
    }

    public function testDepthBoundariesFollowOfficialContract(): void
    {
        new CategoryTreeOptions(0);
        new CategoryTreeOptions(4);
        new CategoryDetailsOptions(0);
        new CategoryDetailsOptions(4);
        self::addToAssertionCount(4);

        foreach ([-1, 5] as $invalid) {
            try {
                new CategoryTreeOptions($invalid);
                self::fail('Expected invalid tree depth.');
            } catch (InvalidRequestException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testOrganizationContextsAreNewImmutableObjects(): void
    {
        [$sdk] = $this->client();
        $first = $sdk->forOrganization(OrganizationId::fromString('organization-1'));
        $second = $sdk->forOrganization(OrganizationId::fromString('organization-2'));

        self::assertNotSame($first, $second);
        self::assertSame('organization-1', $first->organizationId->value);
        self::assertSame('organization-2', $second->organizationId->value);
        self::assertSame($sdk, $first->client());
    }

    /**
     * @return array{VonHalskyClient, FakeHttpClient}
     */
    private function client(Response ...$responses): array
    {
        $http = new FakeHttpClient(array_values($responses));
        $factory = new Psr17Factory();
        $provider = new StaticTokenProvider(new AccessToken(
            'test-token',
            new DateTimeImmutable('2030-01-01T00:00:00+00:00'),
        ));

        return [
            new VonHalskyClient(
                Environment::stage(),
                $provider,
                new HttpClientDependencies($http, $factory, $factory),
            ),
            $http,
        ];
    }

    /** @param array<string, string> $headers */
    private function jsonResponse(mixed $data, int $status = 200, array $headers = []): Response
    {
        $headers['Content-Type'] = 'application/json';

        return new Response($status, $headers, json_encode($data, JSON_THROW_ON_ERROR));
    }

    private function problemResponse(int $status): Response
    {
        return $this->jsonResponse([
            'type' => 'about:blank',
            'title' => 'API error',
            'status' => $status,
            'code' => 'test_error',
        ], $status, ['Content-Type' => 'application/problem+json']);
    }

    /** @return array<string, mixed> */
    private function categoryNode(int $level, int $levels): array
    {
        $node = [
            'id' => 'category-' . $level,
            'name' => 'Level ' . $level,
            'leaf' => $level === $levels - 1,
            'doesNotRequireGpsrInfo' => false,
        ];
        if ($level < $levels - 1) {
            $node['children'] = [$this->categoryNode($level + 1, $levels)];
        }

        return $node;
    }
}
