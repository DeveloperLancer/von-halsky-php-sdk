<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Stage;

use DevLancer\VonHalsky\Exception\ApiException;
use DevLancer\VonHalsky\Model\Attachment\AttachmentType;
use DevLancer\VonHalsky\ValueObject\OfferId;
use PHPUnit\Framework\Attributes\Group;
use Throwable;

#[Group('stage')]
final class PhaseSixAttachmentProbeTest extends StageTestCase
{
    public function testImageUploadAndLocalMimeRejectionOnStage(): void
    {
        $this->expectLocalRejection(function (): void {
            $stream = $this->stageHttp()->streamFactory->createStream(self::pngBytes());
            $this->stageOrganization()->attachments()->upload(
                OfferId::fromString('00000000-0000-4000-8000-000000000000'),
                AttachmentType::IMAGE,
                'probe.png',
                'application/x-not-allowed',
                $stream,
            );
        });

        $created = null;
        $failure = null;
        try {
            $created = $this->createSyntheticOffer();
            $stream = $this->stageHttp()->streamFactory->createStream(self::pngBytes());
            $upload = $this->stageOrganization()->attachments()->upload(
                $created->offerId,
                AttachmentType::IMAGE,
                'sdk-stage-probe.png',
                'image/png',
                $stream,
            );
            self::assertContains($upload->statusCode, [200, 201, 202]);
            $command = $this->waitForCommand($this->stageOrganization()->offers(), $upload->data->commandId);
            self::assertContains($command->status->value, ['SUCCESS', 'FAILURE']);
            if ($command->status->value === 'SUCCESS') {
                $listed = $this->stageOrganization()->attachments()->list($created->offerId)->data;
                self::assertGreaterThanOrEqual(1, count($listed->items));
            }
        } catch (ApiException $exception) {
            $this->assertProblemJson($exception);
        } catch (Throwable $exception) {
            $failure = $exception;
        } finally {
            $this->closeStageOfferQuietly($created?->offerId);
        }

        if ($failure instanceof Throwable) {
            throw $failure;
        }
    }

    private static function pngBytes(): string
    {
        $decoded = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
        if (!is_string($decoded) || $decoded === '') {
            self::fail('The Stage attachment fixture PNG could not be decoded.');
        }

        return $decoded;
    }
}
