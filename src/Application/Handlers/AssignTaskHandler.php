<?php

namespace Src\Application\Handlers;

use Src\Domain\Project\Contracts\ProjectRepository;
use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\TaskId;

final class AssignTaskHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function handle(ProjectId $projectId, TaskId $taskId, UserId $assigneeId): void
    {
        // 1. Carregar Project
        $project = $this->projectRepository->getById($projectId);

        if ($project === null) {
            throw new \DomainException('Project not found.');
        }

        // 2. Executar ação de domínio
        $project->assignTask($taskId, $assigneeId);

        // 3. Salvar alterações
        $this->projectRepository->save($project);

        // (Opcional) Publicar eventos: $project->pullDomainEvents()
    }
}
