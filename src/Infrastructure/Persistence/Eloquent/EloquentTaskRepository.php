<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\Task as TaskModel;
use ReflectionClass;
use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\Contracts\TaskRepository;
use Src\Domain\Task\Task;
use Src\Domain\Task\TaskId;
use Src\Domain\Task\TaskStatus;

class EloquentTaskRepository implements TaskRepository
{
    public function save(Task $task): void
    {
        TaskModel::updateOrCreate(
            ['id' => $task->id->value],
            [
                'project_id' => $task->projectId->value,
                'title' => $task->title,
                'description' => $task->description,
                'status' => $task->status->value,
                'assignee_id' => $task->assigneeId?->value,
            ]
        );
    }

    public function getById(TaskId $id): ?Task
    {
        $model = TaskModel::find($id->value);

        if (! $model) {
            return null;
        }

        return $this->hydrateTask($model);
    }

    public function delete(TaskId $id): void
    {
        TaskModel::destroy($id->value);
    }

    private function hydrateTask(TaskModel $model): Task
    {
        $reflection = new ReflectionClass(Task::class);
        $task = $reflection->newInstanceWithoutConstructor();

        // id
        $p = $reflection->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($task, TaskId::fromString($model->id));

        // projectId
        $p = $reflection->getProperty('projectId');
        $p->setAccessible(true);
        $p->setValue($task, ProjectId::fromString($model->project_id));

        // title
        $p = $reflection->getProperty('title');
        $p->setAccessible(true);
        $p->setValue($task, $model->title);

        // description
        $p = $reflection->getProperty('description');
        $p->setAccessible(true);
        $p->setValue($task, $model->description);

        // status
        $p = $reflection->getProperty('status');
        $p->setAccessible(true);
        $p->setValue($task, TaskStatus::from($model->status));

        // assigneeId
        $p = $reflection->getProperty('assigneeId');
        $p->setAccessible(true);
        $assigneeId = $model->assignee_id ? UserId::fromString((string)$model->assignee_id) : null;
        $p->setValue($task, $assigneeId);

        return $task;
    }
}
