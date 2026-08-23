<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\ResponseMappingException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\DomainResponseHydrator;
use DevLancer\VonHalsky\Model\Category\AttributeDefinition;
use DevLancer\VonHalsky\Model\Category\Category;
use DevLancer\VonHalsky\Request\CategoryDetailsOptions;
use DevLancer\VonHalsky\Request\CategoryTreeOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\ValueObject\CategoryId;

/** Read-only access to category trees, details, and attribute definitions. */
final class CategoriesResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /**
     * Requires OAuth scope `api:categories:read`.
     *
     * @return ApiResponse<list<Category>>
     */
    public function list(?CategoryTreeOptions $options = null): ApiResponse
    {
        $options ??= new CategoryTreeOptions();
        /** @var array<string, int|string> $query */
        $query = ['depth' => $options->depth];
        if ($options->root !== null) {
            $query['categoryId'] = $options->root->value;
        }
        $response = $this->executor->execute(
            'GET',
            '/v1/categories',
            $query,
            self::languageHeader($options->language),
        );
        $data = DomainResponseHydrator::categories(
            $this->decoder->decodeList($response, 'getCategoriesV1'),
        );

        return ApiResponse::fromResponse($data, $response);
    }

    /**
     * Requires OAuth scope `api:categories:read`.
     *
     * @return ApiResponse<Category>
     */
    public function get(CategoryId $categoryId, ?CategoryDetailsOptions $options = null): ApiResponse
    {
        $options ??= new CategoryDetailsOptions();
        $response = $this->executor->execute(
            'GET',
            self::path($categoryId),
            ['depth' => $options->depth],
            self::languageHeader($options->language),
        );
        $decoded = $this->decoder->decodeObject($response, 'getCategoriesByIdV1');
        if ($decoded === null) {
            throw new ResponseMappingException('$', 'category response cannot be empty');
        }

        return ApiResponse::fromResponse(DomainResponseHydrator::category($decoded), $response);
    }

    /**
     * Requires OAuth scope `api:categories:read`.
     *
     * @return ApiResponse<list<AttributeDefinition>>
     */
    public function attributes(CategoryId $categoryId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute(
            'GET',
            self::path($categoryId) . '/attributes',
            [],
            self::languageHeader($language),
        );
        $data = DomainResponseHydrator::attributes(
            $this->decoder->decodeList($response, 'getCategoriesAttributesByCategoryIdV1'),
        );

        return ApiResponse::fromResponse($data, $response);
    }

    private static function path(CategoryId $categoryId): string
    {
        return '/v1/categories/' . rawurlencode($categoryId->value);
    }

    /** @return array<string, string> */
    private static function languageHeader(?ResponseLanguage $language): array
    {
        return $language === null ? [] : ['Accept-Language' => $language->value];
    }
}
