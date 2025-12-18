<?php

namespace Tests\Unit\Domain;

use PHPUnit\Framework\TestCase;
use Src\Domain\Project\Project;
use Src\Domain\Project\ProjectId;
use Src\Domain\Project\Events\MemberAdded;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\Events\TaskAssigned;
use Src\Domain\Task\Events\TaskCreated;
use Src\Domain\Task\TaskId;

class ProjectTest extends TestCase
{
    public function test_can_create_project_with_owner_as_first_member(): void
    {
        $projectId = ProjectId::fromString('proj-1');
        $ownerId = UserId::fromString('user-1');
        
        $project = Project::create($projectId, 'Test Project', $ownerId);

        $this->assertTrue($project->isMember($ownerId));
        $this->assertCount(1, $project->pullDomainEvents());
    }

    public function test_can_add_member_to_project(): void
    {
        $project = Project::create(
            ProjectId::fromString('proj-1'),
            'Test Project',
            UserId::fromString('owner-1')
        );
        $newMemberId = UserId::fromString('user-2');

        $project->addMember($newMemberId);

        $this->assertTrue($project->isMember($newMemberId));
        
        $events = $project->pullDomainEvents();
        // Evento 0: criação (MemberAdded do owner)
        // Evento 1: MemberAdded do novo membro
        $this->assertInstanceOf(MemberAdded::class, $events[1]);
        $this->assertEquals($newMemberId, $events[1]->memberId);
    }

    public function test_can_create_task_in_project(): void
    {
        $project = Project::create(
            ProjectId::fromString('proj-1'),
            'Test Project',
            UserId::fromString('owner-1')
        );
        $taskId = TaskId::fromString('task-1');

        $project->createTask($taskId, 'Task Title', 'Task Desc');

        $tasks = $project->tasks();
        $this->assertCount(1, $tasks);
        $this->assertEquals('task-1', $tasks[0]->id->value);

        $events = $project->pullDomainEvents();
        $lastEvent = end($events);
        $this->assertInstanceOf(TaskCreated::class, $lastEvent);
    }

    public function test_can_assign_task_to_project_member(): void
    {
        $project = Project::create(
            ProjectId::fromString('proj-1'),
            'Test Project',
            UserId::fromString('owner-1')
        );
        $memberId = UserId::fromString('user-2');
        $project->addMember($memberId);
        
        $taskId = TaskId::fromString('task-1');
        $project->createTask($taskId, 'Task Title', 'Task Desc');

        // Action
        $project->assignTask($taskId, $memberId);

        // Assert
        $task = $project->tasks()[0];
        $this->assertEquals($memberId, $task->assigneeId);

        $events = $project->pullDomainEvents();
        $lastEvent = end($events);
        $this->assertInstanceOf(TaskAssigned::class, $lastEvent);
        $this->assertEquals($memberId, $lastEvent->assigneeId);
    }

    public function test_cannot_assign_task_to_non_member(): void
    {
        $project = Project::create(
            ProjectId::fromString('proj-1'),
            'Test Project',
            UserId::fromString('owner-1')
        );
        $taskId = TaskId::fromString('task-1');
        $project->createTask($taskId, 'Task Title', 'Task Desc');
        
        $nonMemberId = UserId::fromString('intruder-1');

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('User must be a member');

        $project->assignTask($taskId, $nonMemberId);
    }
}
