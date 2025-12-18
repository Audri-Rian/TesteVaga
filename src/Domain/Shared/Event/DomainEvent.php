<?php

namespace Src\Domain\Shared\Event;

interface DomainEvent
{
    public function occurredAt(): \DateTimeImmutable;
}
