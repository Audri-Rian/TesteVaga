<?php

namespace Src\Domain\Task;

use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;

/**
 * Entidade de Domínio Task.
 * Não é aggregate root (é parte do Project Aggregate).
 */
final class Task
{
    private function __construct(
        public readonly TaskId $id,
        public readonly ProjectId $projectId,
        public readonly string $title,
        public readonly string $description,
        public TaskStatus $status,
        public ?UserId $assigneeId = null
    ) {}

    public static function create(
        TaskId $id,
        ProjectId $projectId,
        string $title,
        string $description
    ): self {
        return new self(
            $id,
            $projectId,
            $title,
            $description,
            TaskStatus::Pending
        );
    }

    public function assignTo(UserId $userId): void
    {
        // A validação se o user é membro do projeto acontece no Aggregate PROJECT
        $this->assigneeId = $userId;
    }

    public function markAs(TaskStatus $status): void
    {
        $this->status = $status;
    }
}
