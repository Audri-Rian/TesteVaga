<?php

namespace Src\Domain\Project\Contracts;

use Src\Domain\Project\Project;
use Src\Domain\Project\ProjectId;

interface ProjectRepository
{
    public function save(Project $project): void;
    public function getById(ProjectId $id): ?Project;
    public function delete(ProjectId $id): void;
}
