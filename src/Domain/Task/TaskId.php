<?php

namespace Src\Domain\Task;

final class TaskId
{
    private function __construct(public readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('TaskId não pode ser vazio mizeravi.');
        }

        return new self($value);
    }
}
