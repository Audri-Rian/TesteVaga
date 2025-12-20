<?php

namespace Src\Application\DTOs;

final readonly class AddCommentDTO
{
    public function __construct(
        public string $taskId,
        public string $userId,
        public string $content
    ) {}
}
