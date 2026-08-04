<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

/** Raised when no usable HTTP response can be obtained due to a network failure. */
final class NetworkTransportException extends TransportException
{
}
