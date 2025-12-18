<?php

namespace Src\Domain\Task\Contracts;

use Src\Domain\Task\Task;
use Src\Domain\Task\TaskId;

interface TaskRepository
{
    public function save(Task $task): void;
    public function getById(TaskId $id): ?Task;
    public function delete(TaskId $id): void;
}
