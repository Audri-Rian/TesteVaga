<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\Project as ProjectModel;
use App\Models\User as UserModel;
use ReflectionClass;
use Src\Domain\Project\Contracts\ProjectRepository;
use Src\Domain\Project\Project;
use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\Task;
use Src\Domain\Task\TaskId;
use Src\Domain\Task\TaskStatus;

class EloquentProjectRepository implements ProjectRepository
{
    public function save(Project $project): void
    {
        // 1. Persist the Project Aggregate Root
        $projectModel = ProjectModel::updateOrCreate(
            ['id' => $project->id->value],
            ['name' => $project->name]
        );

        // 2. Sync Members
        // Get all member IDs from the domain entity
        $memberIds = array_map(fn(UserId $id) => $id->value, $project->members());
        // We sync using the pivot table. access_level is stored on pivot in DB if needed, 
        // but domain currently doesn't expose it deeply in the interface yet.
        // Assuming simple sync for now.
        $projectModel->members()->sync($memberIds);

        // 3. Persist Tasks (Tasks are part of the aggregate, usually saved via the aggregate repo)
        // However, since we have a separate TaskRepository, we might delegate or save here.
        // For strict DDD, the Aggregate Repository should save everything.
        foreach ($project->tasks() as $task) {
           // We will save tasks here to ensure consistency
           // Logic similar to TaskRepository save, but batched/related
           \App\Models\Task::updateOrCreate(
               ['id' => $task->id->value],
               [
                   'project_id' => $project->id->value,
                   'title' => $task->title,
                   'description' => $task->description,
                   'status' => $task->status->value,
                   'assignee_id' => $task->assigneeId?->value,
               ]
           );
        }
    }

    public function getById(ProjectId $id): ?Project
    {
        $projectModel = ProjectModel::with(['members', 'tasks'])->find($id->value);

        if (! $projectModel) {
            return null;
        }

        return $this->hydrateProject($projectModel);
    }

    public function delete(ProjectId $id): void
    {
        ProjectModel::destroy($id->value);
    }

    private function hydrateProject(ProjectModel $model): Project
    {
        $reflection = new ReflectionClass(Project::class);
        $project = $reflection->newInstanceWithoutConstructor();

        // Set ID
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($project, ProjectId::fromString($model->id));

        // Set Name
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setAccessible(true);
        $nameProperty->setValue($project, $model->name);

        // Set Members
        $members = [];
        foreach ($model->members as $memberModel) {
             // In Project.php: private array $members = []; // [string value => UserId]
             // User ID from DB is int, but UserId VO expects string/int? 
             // Ideally modify UserId to accept int or cast to string.
             // Assuming User model ID is int, standard casting:
             $userId = UserId::fromString((string)$memberModel->id);
             $members[$userId->value] = $userId;
        }
        $membersProperty = $reflection->getProperty('members');
        $membersProperty->setAccessible(true);
        $membersProperty->setValue($project, $members);

        // Set Tasks
        $tasks = [];
        foreach ($model->tasks as $taskModel) {
            $tasks[$taskModel->id] = $this->hydrateTask($taskModel);
        }
        $tasksProperty = $reflection->getProperty('tasks');
        $tasksProperty->setAccessible(true);
        $tasksProperty->setValue($project, $tasks);

        return $project;
    }

    private function hydrateTask(\App\Models\Task $model): Task
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
