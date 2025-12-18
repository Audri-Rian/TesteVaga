<?php

namespace Tests\Unit\Application;

use PHPUnit\Framework\TestCase;
use Src\Application\Handlers\AssignTaskHandler;
use Src\Domain\Project\Project;
use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\TaskId;
use Src\Infrastructure\Persistence\InMemory\InMemoryProjectRepository;

class AssignTaskHandlerTest extends TestCase
{
    private InMemoryProjectRepository $repository;
    private AssignTaskHandler $handler;

    protected function setUp(): void
    {
        $this->repository = new InMemoryProjectRepository();
        $this->handler = new AssignTaskHandler($this->repository);
    }

    public function test_successfully_assigns_task_to_member(): void
    {
        // Arrange
        $projectId = ProjectId::fromString('proj-1');
        $ownerId = UserId::fromString('owner-1');
        $project = Project::create($projectId, 'Test Project', $ownerId);
        
        $taskId = TaskId::fromString('task-1');
        $project->createTask($taskId, 'Title', 'Desc');
        
        $this->repository->save($project);

        // Act
        $this->handler->handle($projectId, $taskId, $ownerId);

        // Assert
        $updatedProject = $this->repository->getById($projectId);
        $task = $updatedProject->tasks()[0];
        
        $this->assertEquals($ownerId, $task->assigneeId);
    }

    public function test_throws_exception_when_project_not_found(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Project not found');

        $this->handler->handle(
            ProjectId::fromString('proj-999'),
            TaskId::fromString('task-1'),
            UserId::fromString('user-1')
        );
    }
}
