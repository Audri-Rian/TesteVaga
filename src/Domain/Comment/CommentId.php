<?php

namespace Src\Domain\Comment;

final class CommentId
{
    private function __construct(public readonly string $value) {}

    public static function fromString(string $value): self
    {
        if (trim($value) === '') {
            throw new \InvalidArgumentException('CommentId não pode ser vazio mizeravi.');
        }

        return new self($value);
    }
}
