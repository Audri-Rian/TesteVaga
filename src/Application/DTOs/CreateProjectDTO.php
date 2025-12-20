<?php

namespace Src\Application\DTOs;

final readonly class CreateProjectDTO
{
    public function __construct(
        public string $name,
        public string $ownerId
    ) {}
}
