<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

final class FakeRequestException extends RuntimeException implements RequestExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, string $message)
    {
        parent::__construct($message);
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
