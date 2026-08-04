<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Unit\Http;

use DevLancer\VonHalsky\Http\Body\MultipartPart;
use DevLancer\VonHalsky\Http\Body\MultipartStreamBuilder;
use DevLancer\VonHalsky\Tests\Support\NonSeekableStream;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;

final class MultipartStreamBuilderTest extends TestCase
{
    public function testStreamsLargeFileAndSupportsSeekingWhenEveryPartDoes(): void
    {
        $factory = new Psr17Factory();
        $file = tmpfile();
        self::assertIsResource($file);
        $chunk = str_repeat('0123456789abcdef', 4096);
        for ($i = 0; $i < 48; ++$i) {
            fwrite($file, $chunk);
        }
        rewind($file);

        $body = (new MultipartStreamBuilder($factory))->build([
            new MultipartPart('file', $factory->createStreamFromResource($file), 'large.bin'),
        ], 'test-boundary');

        self::assertTrue($body->stream->isSeekable());
        self::assertGreaterThan(3 * 1024 * 1024, $body->stream->getSize());
        self::assertSame('--test-boundary', $body->stream->read(15));
        $body->stream->rewind();
        self::assertSame('--test-boundary', $body->stream->read(15));
    }

    public function testRemainsReadableWithNonSeekablePart(): void
    {
        $factory = new Psr17Factory();
        $stream = new NonSeekableStream($factory->createStream('streamed payload'));
        self::assertFalse($stream->isSeekable());

        $body = (new MultipartStreamBuilder($factory))->build([
            new MultipartPart('stream', $stream),
        ], 'non-seekable');

        self::assertFalse($body->stream->isSeekable());
        self::assertStringStartsWith("--non-seekable\r\n", $body->stream->read(128));
    }
}
