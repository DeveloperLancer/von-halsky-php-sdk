<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use LogicException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeHttpClient implements ClientInterface
{
    /** @var list<ResponseInterface|ClientExceptionInterface> */
    private array $queue;

    /** @var list<RequestInterface> */
    private array $requests = [];

    /** @param list<ResponseInterface|ClientExceptionInterface> $queue */
    public function __construct(array $queue = [])
    {
        $this->queue = $queue;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;
        $next = array_shift($this->queue);
        if ($next === null) {
            throw new LogicException('The fake HTTP response queue is empty.');
        }
        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }

    public function enqueue(ResponseInterface|ClientExceptionInterface $result): void
    {
        $this->queue[] = $result;
    }

    /** @return list<RequestInterface> */
    public function requests(): array
    {
        return $this->requests;
    }

    public function requestAt(int $index): RequestInterface
    {
        if (!isset($this->requests[$index])) {
            throw new LogicException('The requested fake HTTP request does not exist.');
        }

        return $this->requests[$index];
    }
}
