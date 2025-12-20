<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Task as EloquentTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;
use Src\Application\DTOs\CreateTaskDTO;
use Src\Application\Handlers\AssignTaskHandler;
use Src\Application\Handlers\CreateTaskHandler;
use Src\Domain\Project\ProjectId;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\TaskId;
use Src\Domain\Task\TaskStatus;

class TaskController extends Controller
{
    public function __construct(
        private readonly CreateTaskHandler $createTaskHandler,
        private readonly AssignTaskHandler $assignTaskHandler
    ) {}

    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = EloquentTask::with(['project', 'assignee'])
            ->whereHas('project.members', function ($q) use ($request) {
                $q->where('user_id', $request->user()->id);
            });

        // Filtros opcionais
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('project_id')) {
            $query->where('project_id', $request->input('project_id'));
        }

        if ($request->has('assignee_id')) {
            $query->where('assignee_id', $request->input('assignee_id'));
        }

        $tasks = $query->latest()->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Store a newly created task.
     */
    public function store(CreateTaskRequest $request): JsonResponse
    {
        $dto = new CreateTaskDTO(
            projectId: $request->validated('project_id'),
            title: $request->validated('title'),
            description: $request->validated('description')
        );

        $this->createTaskHandler->handle($dto);

        // Buscar a tarefa criada
        $task = EloquentTask::with(['project', 'assignee'])
            ->where('project_id', $request->validated('project_id'))
            ->latest()
            ->first();

        return response()->json(
            new TaskResource($task),
            201
        );
    }

    /**
     * Display the specified task.
     */
    public function show(Request $request, string $id): TaskResource
    {
        $task = EloquentTask::with(['project', 'assignee', 'comments.user'])
            ->findOrFail($id);

        // Verificar se o usuário é membro do projeto
        if (!$task->project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        return new TaskResource($task);
    }

    /**
     * Update the task status.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'status' => [
                'required',
                Rule::in([
                    ...array_map(fn (TaskStatus $status) => $status->value, TaskStatus::cases()),
                    'Pending',
                    'InProgress',
                    'Completed',
                    'Done',
                ]),
            ],
        ]);

        $task = EloquentTask::with(['project'])->findOrFail($id);

        // Verificar se o usuário é membro do projeto
        if (!$task->project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        $status = $this->normalizeStatus($request->input('status'));

        $task->status = $status->value;
        $task->save();

        $task->load(['assignee', 'comments.user']);

        return response()->json(
            new TaskResource($task),
            200
        );
    }

    /**
     * Assign the task to a user.
     */
    public function assign(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'assignee_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $task = EloquentTask::with(['project'])->findOrFail($id);

        // Verificar se o usuário atual é membro do projeto
        if (!$task->project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        $this->assignTaskHandler->handle(
            ProjectId::fromString($task->project_id),
            TaskId::fromString($id),
            UserId::fromString($request->input('assignee_id'))
        );

        $task->refresh();
        $task->load(['assignee', 'comments.user']);

        return response()->json(
            new TaskResource($task),
            200
        );
    }

    private function normalizeStatus(string $status): TaskStatus
    {
        $normalized = strtolower($status);

        return match ($normalized) {
            'pending' => TaskStatus::Pending,
            'inprogress', 'in_progress' => TaskStatus::InProgress,
            'completed', 'done' => TaskStatus::Done,
            default => TaskStatus::from($status),
        };
    }
}
