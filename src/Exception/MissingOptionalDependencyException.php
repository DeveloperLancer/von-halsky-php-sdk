<?php

declare(strict_types=1);

namespace DevLancer\VonHalsky\Exception;

/** Raised only when a feature whose optional package is absent is selected. */
final class MissingOptionalDependencyException extends ConfigurationException
{
    private function __construct(
        public readonly string $package,
        public readonly string $feature,
        public readonly string $installCommand,
    ) {
        parent::__construct(sprintf(
            'The optional package "%s" is required for %s. Install it with `%s`.',
            $package,
            $feature,
            $installCommand,
        ));
    }

    public static function forPackage(string $package, string $feature, string $installCommand): self
    {
        return new self($package, $feature, $installCommand);
    }
}
