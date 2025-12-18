<?php

namespace Src\Infrastructure\Persistence\InMemory;

use Src\Domain\Project\Contracts\ProjectRepository;
use Src\Domain\Project\Project;
use Src\Domain\Project\ProjectId;

final class InMemoryProjectRepository implements ProjectRepository
{
    /** @var array<string, Project> */
    private array $projects = [];

    public function save(Project $project): void
    {
        // Simulando persistência (clone para evitar referência direta)
        $this->projects[$project->id->value] = clone $project;
    }

    public function getById(ProjectId $id): ?Project
    {
        if (! isset($this->projects[$id->value])) {
            return null;
        }

        return clone $this->projects[$id->value];
    }

    public function delete(ProjectId $id): void
    {
        unset($this->projects[$id->value]);
    }
}
