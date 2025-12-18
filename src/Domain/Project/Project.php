<?php

namespace Src\Domain\Project;

use Src\Domain\Project\Events\MemberAdded;
use Src\Domain\Shared\Event\RecordsEvents;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\Events\TaskAssigned;
use Src\Domain\Task\Events\TaskCreated;
use Src\Domain\Task\Task;
use Src\Domain\Task\TaskId;

final class Project
{
    use RecordsEvents;

    /**
     * @param UserId[] $members Associative array [string value => UserId]
     * @param Task[] $tasks Associative array [string value => Task]
     */
    private function __construct(
        public readonly ProjectId $id,
        public readonly string $name,
        private array $members = [],
        private array $tasks = []
    ) {}

    public static function create(
        ProjectId $id,
        string $name,
        UserId $ownerId
    ): self {
        $project = new self($id, $name);
        $project->addMember($ownerId);
        
        return $project;
    }

    public function addMember(UserId $memberId): void
    {
        if (array_key_exists($memberId->value, $this->members)) {
            return;
        }

        $this->members[$memberId->value] = $memberId;

        $this->recordThat(new MemberAdded($this->id, $memberId));
    }

    public function createTask(TaskId $taskId, string $title, string $description): void
    {
        $task = Task::create($taskId, $this->id, $title, $description);
        
        $this->tasks[$taskId->value] = $task;

        $this->recordThat(new TaskCreated($taskId, $this->id));
    }

    public function assignTask(TaskId $taskId, UserId $assigneeId): void
    {
        // 1. Invariante: Só membros podem assumir tarefas
        if (! array_key_exists($assigneeId->value, $this->members)) {
            throw new \DomainException('User must be a member of the project to be assigned a task.');
        }

        // 2. Localiza a task
        if (! array_key_exists($taskId->value, $this->tasks)) {
            throw new \DomainException('Task not found in this project.');
        }

        $task = $this->tasks[$taskId->value];

        // 3. Delega para a entidade Task
        $task->assignTo($assigneeId);

        // 4. Registra evento de domínio
        $this->recordThat(new TaskAssigned($this->id, $taskId, $assigneeId));
    }

    public function members(): array
    {
        return array_values($this->members);
    }

    public function tasks(): array
    {
        return array_values($this->tasks);
    }

    public function isMember(UserId $userId): bool
    {
        return array_key_exists($userId->value, $this->members);
    }
}
