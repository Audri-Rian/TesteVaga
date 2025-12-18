<?php

namespace Src\Domain\Project\Events;

use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\Event\DomainEvent;

final class ProjectCreated implements DomainEvent
{
    public function __construct(
        public readonly ProjectId $projectId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
