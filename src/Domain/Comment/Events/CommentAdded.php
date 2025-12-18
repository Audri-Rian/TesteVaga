<?php

namespace Src\Domain\Comment\Events;

use Src\Domain\Comment\CommentId;
use Src\Domain\Shared\Event\DomainEvent;

final class CommentAdded implements DomainEvent
{
    public function __construct(
        public readonly CommentId $commentId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
