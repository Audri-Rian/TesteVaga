<?php

namespace Src\Domain\Project\Events;

use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\Event\DomainEvent;
use Src\Domain\Shared\UserId;

final class MemberAdded implements DomainEvent
{
    public function __construct(
        public readonly ProjectId $projectId,
        public readonly UserId $memberId,
        private readonly \DateTimeImmutable $occurredAt = new \DateTimeImmutable()
    ) {}

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->occurredAt;
    }
}
