<?php

namespace Src\Application\Handlers;

use Src\Application\DTOs\CreateTaskDTO;
use Src\Domain\Project\Contracts\ProjectRepository;
use Src\Domain\Project\ProjectId;
use Src\Domain\Task\TaskId;

final class CreateTaskHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function handle(CreateTaskDTO $dto): void
    {
        // 1. Carregar Project aggregate
        $projectId = ProjectId::fromString($dto->projectId);
        $project = $this->projectRepository->getById($projectId);

        if ($project === null) {
            throw new \DomainException('Project not found.');
        }

        // 2. Criar tarefa através do aggregate
        $taskId = TaskId::fromString(\Illuminate\Support\Str::uuid()->toString());
        $project->createTask($taskId, $dto->title, $dto->description);

        // 3. Persistir alterações
        $this->projectRepository->save($project);
    }
}
