<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

/** Raised when the API rejects an access token with HTTP 401. */
final class AuthenticationException extends ApiException
{
}
