<?php

namespace Src\Domain\Task\Events;

use Src\Domain\Task\TaskId;
use Src\Domain\Shared\Event\DomainEvent;

final class TaskAssigned implements DomainEvent
{
    public function __construct(
        public readonly TaskId $taskId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
