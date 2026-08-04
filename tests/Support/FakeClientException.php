<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Tests\Support;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

final class FakeClientException extends RuntimeException implements ClientExceptionInterface
{
}
