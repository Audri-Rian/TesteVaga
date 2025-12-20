<?php

namespace Src\Application\DTOs;

final readonly class CreateTaskDTO
{
    public function __construct(
        public string $projectId,
        public string $title,
        public string $description
    ) {}
}
