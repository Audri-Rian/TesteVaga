<?php

namespace Src\Application\Handlers;

use Src\Application\DTOs\CreateProjectDTO;
use Src\Domain\Project\Contracts\ProjectRepository;
use Src\Domain\Project\Project;
use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;

final class CreateProjectHandler
{
    public function __construct(
        private readonly ProjectRepository $projectRepository
    ) {}

    public function handle(CreateProjectDTO $dto): Project
    {
        // 1. Criar IDs
        $projectId = ProjectId::fromString(\Illuminate\Support\Str::uuid()->toString());
        $ownerId = UserId::fromString($dto->ownerId);

        // 2. Criar aggregate Project
        $project = Project::create($projectId, $dto->name, $ownerId);

        // 3. Persistir
        $this->projectRepository->save($project);

        return $project;
    }
}
