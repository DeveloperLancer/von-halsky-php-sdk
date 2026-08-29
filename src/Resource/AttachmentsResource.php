<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Resource;

use DevLancer\VonHalsky\Exception\InvalidRequestException;
use DevLancer\VonHalsky\Http\ApiResponse;
use DevLancer\VonHalsky\Http\Body\MultipartPart;
use DevLancer\VonHalsky\Http\RequestExecutor;
use DevLancer\VonHalsky\Internal\OfferResponseHydrator;
use DevLancer\VonHalsky\Model\Attachment\AttachmentInfo;
use DevLancer\VonHalsky\Model\Attachment\AttachmentType;
use DevLancer\VonHalsky\Model\Attachment\DownloadedAttachment;
use DevLancer\VonHalsky\Model\Offer\CommandHandle;
use DevLancer\VonHalsky\Pagination\PageResult;
use DevLancer\VonHalsky\Request\AttachmentListOptions;
use DevLancer\VonHalsky\Request\ResponseLanguage;
use DevLancer\VonHalsky\Serialization\JsonResponseDecoder;
use DevLancer\VonHalsky\Validation\RequestValidator;
use DevLancer\VonHalsky\ValueObject\AttachmentId;
use DevLancer\VonHalsky\ValueObject\OfferId;
use DevLancer\VonHalsky\ValueObject\OrganizationId;
use Psr\Http\Message\StreamInterface;

/** Stream-first offer attachment operations. */
final class AttachmentsResource
{
    public function __construct(
        private readonly RequestExecutor $executor,
        private readonly OrganizationId $organizationId,
        private readonly JsonResponseDecoder $decoder = new JsonResponseDecoder(),
    ) {
    }

    /**
     * Requires OAuth scope `api:offers:read`.
     *
     * @return ApiResponse<PageResult<AttachmentInfo>>
     */
    public function list(OfferId $offerId, ?AttachmentListOptions $options = null): ApiResponse
    {
        $options ??= new AttachmentListOptions();
        $response = $this->executor->execute('GET', $this->basePath($offerId), ['limit' => $options->limit, 'offset' => $options->offset], self::language($options->language));
        $data = $this->decoder->decodeObject($response, 'getOffersAttachmentsByOfferIdV1') ?? [];

        return ApiResponse::fromResponse(OfferResponseHydrator::attachments($data), $response);
    }

    /**
     * The upload stream is consumed but never buffered or closed by the SDK.
     * Requires OAuth scope `api:offers:write`.
     *
     * @return ApiResponse<CommandHandle>
     */
    public function upload(OfferId $offerId, AttachmentType $type, string $filename, string $mimeType, StreamInterface $stream, ?ResponseLanguage $language = null): ApiResponse
    {
        if (strlen($filename) > 500) {
            throw new InvalidRequestException('attachment.filename', 'must contain at most 500 bytes');
        }
        if ($type === AttachmentType::IMAGE) {
            RequestValidator::offerImageFileName($filename, 'attachment.filename');
        }
        if (preg_match('/\A[a-zA-Z0-9!#$&^_.+-]+\/[a-zA-Z0-9!#$&^_.+-]+\z/D', $mimeType) !== 1) {
            throw new InvalidRequestException('attachment.mimeType', 'must be a valid MIME type');
        }
        if (!$type->allowsMimeType($mimeType)) {
            throw new InvalidRequestException('attachment.mimeType', sprintf('is not permitted for attachment type %s', $type->value));
        }
        $response = $this->executor->executeMultipart(
            'POST',
            $this->basePath($offerId),
            [new MultipartPart('file', $stream, $filename, ['Content-Type' => $mimeType])],
            self::language($language),
            ['attachmentType' => $type->value],
        );
        $data = $this->decoder->decodeObject($response, 'postOffersAttachmentsByOfferIdV1') ?? [];

        return ApiResponse::fromResponse(OfferResponseHydrator::handle($data), $response);
    }

    /**
     * The caller owns the returned response stream and must close it.
     * Requires OAuth scope `api:offers:read`.
     *
     * @return ApiResponse<DownloadedAttachment>
     */
    public function download(OfferId $offerId, AttachmentId $attachmentId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('GET', $this->attachmentPath($offerId, $attachmentId), [], self::language($language) + ['Accept' => 'application/octet-stream']);
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            $this->decoder->decodeObject($response, 'getOffersAttachmentsByOfferIdAndAttachmentIdV1');
        }
        $disposition = $response->getHeaderLine('Content-Disposition');
        $filename = preg_match('/filename="?([^";]+)"?/i', $disposition, $matches) === 1 ? $matches[1] : null;
        $size = $response->getBody()->getSize();
        $download = new DownloadedAttachment(
            $response->getBody(),
            $response->getHeaderLine('Content-Type') !== '' ? $response->getHeaderLine('Content-Type') : null,
            $filename,
            $size,
        );

        return ApiResponse::fromResponse($download, $response);
    }

    /**
     * Requires OAuth scope `api:offers:write`.
     *
     * @return ApiResponse<null>
     */
    public function delete(OfferId $offerId, AttachmentId $attachmentId, ?ResponseLanguage $language = null): ApiResponse
    {
        $response = $this->executor->execute('DELETE', $this->attachmentPath($offerId, $attachmentId), [], self::language($language));
        $this->decoder->decodeObject($response, 'deleteOffersAttachmentsByOfferIdAndAttachmentIdV1');

        return ApiResponse::fromResponse(null, $response);
    }

    private function basePath(OfferId $offerId): string
    {
        return '/v1/organizations/' . rawurlencode($this->organizationId->value) . '/offers/' . rawurlencode($offerId->value) . '/attachments';
    }

    private function attachmentPath(OfferId $offerId, AttachmentId $attachmentId): string
    {
        return $this->basePath($offerId) . '/' . rawurlencode($attachmentId->value);
    }

    /** @return array<string, string> */
    private static function language(?ResponseLanguage $language): array
    {
        return $language === null ? [] : ['Accept-Language' => $language->value];
    }
}
