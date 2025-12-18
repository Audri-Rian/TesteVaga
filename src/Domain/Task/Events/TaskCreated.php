<?php

namespace Src\Domain\Task\Events;

use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\Event\DomainEvent;
use Src\Domain\Task\TaskId;

final class TaskCreated implements DomainEvent
{
    public function __construct(
        public readonly TaskId $taskId,
        public readonly ProjectId $projectId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
