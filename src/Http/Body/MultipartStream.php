<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Http\Body;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use Throwable;

/** @internal Read-only concatenation of multipart framing and content streams. */
final class MultipartStream implements StreamInterface
{
    private int $index = 0;

    private int $position = 0;

    private bool $closed = false;

    /** @param list<StreamInterface> $streams */
    public function __construct(private array $streams)
    {
    }

    public function __toString(): string
    {
        try {
            if ($this->isSeekable()) {
                $this->rewind();
            }

            return $this->getContents();
        } catch (Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        foreach ($this->streams as $stream) {
            $stream->close();
        }

        $this->streams = [];
        $this->closed = true;
    }

    /** @return null */
    public function detach()
    {
        foreach ($this->streams as $stream) {
            $stream->detach();
        }

        $this->streams = [];
        $this->closed = true;

        return null;
    }

    public function getSize(): ?int
    {
        $size = 0;
        foreach ($this->streams as $stream) {
            $partSize = $stream->getSize();
            if ($partSize === null) {
                return null;
            }
            $size += $partSize;
        }

        return $size;
    }

    public function tell(): int
    {
        $this->assertOpen();

        return $this->position;
    }

    public function eof(): bool
    {
        return $this->closed || $this->index >= count($this->streams);
    }

    public function isSeekable(): bool
    {
        if ($this->closed) {
            return false;
        }

        foreach ($this->streams as $stream) {
            if (!$stream->isSeekable()) {
                return false;
            }
        }

        return true;
    }

    public function seek(int $offset, int $whence = SEEK_SET): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('The multipart stream is not seekable.');
        }

        $size = $this->getSize();
        $target = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $size === null ? -1 : $size + $offset,
            default => -1,
        };

        if ($target < 0 || ($size !== null && $target > $size)) {
            throw new RuntimeException('Cannot seek to the requested multipart stream position.');
        }

        $this->rewind();
        $remaining = $target;
        while ($remaining > 0) {
            $chunk = $this->read(min(8192, $remaining));
            if ($chunk === '') {
                throw new RuntimeException('Cannot seek to the requested multipart stream position.');
            }
            $remaining -= strlen($chunk);
        }
    }

    public function rewind(): void
    {
        if (!$this->isSeekable()) {
            throw new RuntimeException('The multipart stream is not seekable.');
        }

        foreach ($this->streams as $stream) {
            $stream->rewind();
        }
        $this->index = 0;
        $this->position = 0;
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write(string $string): int
    {
        throw new RuntimeException('The multipart stream is read-only.');
    }

    public function isReadable(): bool
    {
        return !$this->closed;
    }

    public function read(int $length): string
    {
        $this->assertOpen();
        if ($length < 0) {
            throw new RuntimeException('The read length cannot be negative.');
        }
        if ($length === 0) {
            return '';
        }

        $result = '';
        while (strlen($result) < $length && isset($this->streams[$this->index])) {
            $stream = $this->streams[$this->index];
            $chunk = $stream->read($length - strlen($result));
            $result .= $chunk;

            if ($stream->eof()) {
                ++$this->index;
                continue;
            }

            if ($chunk === '') {
                break;
            }
        }

        $this->position += strlen($result);

        return $result;
    }

    public function getContents(): string
    {
        $contents = '';
        while (!$this->eof()) {
            $chunk = $this->read(8192);
            if ($chunk === '') {
                break;
            }
            $contents .= $chunk;
        }

        return $contents;
    }

    public function getMetadata(?string $key = null): mixed
    {
        $metadata = [
            'eof' => $this->eof(),
            'seekable' => $this->isSeekable(),
            'stream_type' => 'von-halsky-multipart',
        ];

        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }

    private function assertOpen(): void
    {
        if ($this->closed) {
            throw new RuntimeException('The multipart stream is closed.');
        }
    }
}
