<?php

namespace Src\Domain\Project;

final class ProjectId
{
    private function __construct(public readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('ProjectId não pode ser vazio mizeravi.');
        }

        return new self($value);
    }
}
