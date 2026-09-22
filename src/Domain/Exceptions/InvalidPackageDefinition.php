<?php

declare(strict_types=1);

namespace Simtabi\Laranail\Ichava\IconSetsPackageScaffolder\Domain\Exceptions;

use InvalidArgumentException;

/**
 * One rejection reason per named constructor, so a caller reads why rather
 * than matching on a message string.
 *
 * These are thrown from `PackageDefinition`'s constructor, which is the only
 * place the rules live. Previously each rule was a closure inside a prompt
 * `validate:` argument, so nothing enforced them when the command was driven
 * by options instead of interactively -- which is how every test and every CI
 * invocation drives it.
 */
final class InvalidPackageDefinition extends InvalidArgumentException
{
    public static function emptyName(): self
    {
        return new self('Package name is required.');
    }

    public static function nameTooShort(string $name): self
    {
        return new self(sprintf('Package name must be at least 2 characters, got "%s".', $name));
    }

    public static function emptyVendor(): self
    {
        return new self('Vendor name is required.');
    }

    public static function invalidEmail(string $email): self
    {
        return new self(sprintf('"%s" is not a valid email address.', $email));
    }

    public static function unknownIconSetType(string $type): self
    {
        return new self(sprintf('Unknown icon set type "%s"; expected "single" or "multi".', $type));
    }

    public static function unknownIconAxis(string $axis): self
    {
        return new self(sprintf('Unknown icon axis "%s"; expected "variant" or "category".', $axis));
    }

    public static function multiSetWithoutVariants(): self
    {
        return new self('A multi-variant package needs at least one variant.');
    }

    public static function duplicateVariant(string $slug): self
    {
        return new self(sprintf('Variant "%s" is listed more than once.', $slug));
    }

    public static function emptyVariant(): self
    {
        return new self('A variant name cannot be empty.');
    }
}
