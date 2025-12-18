<?php

namespace Src\Domain\Shared;

final class UserId
{
    private function __construct(public readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('UserId cannot be empty.');
        }

        return new self($value);
    }
}
