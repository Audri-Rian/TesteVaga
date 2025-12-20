<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment as EloquentComment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Application\DTOs\AddCommentDTO;
use Src\Application\Handlers\AddCommentHandler;

class CommentController extends Controller
{
    public function __construct(
        private readonly AddCommentHandler $addCommentHandler
    ) {}

    /**
     * Display a listing of comments for a task.
     */
    public function index(Request $request, string $taskId): AnonymousResourceCollection
    {
        $task = Task::with(['project'])->findOrFail($taskId);

        // Verificar se o usuário é membro do projeto
        if (!$task->project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        $comments = EloquentComment::with(['user'])
            ->where('task_id', $taskId)
            ->latest()
            ->get();

        return CommentResource::collection($comments);
    }

    /**
     * Store a newly created comment.
     */
    public function store(AddCommentRequest $request): JsonResponse
    {
        $task = Task::with(['project'])->findOrFail($request->validated('task_id'));

        // Verificar se o usuário é membro do projeto
        if (!$task->project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        $dto = new AddCommentDTO(
            taskId: $request->validated('task_id'),
            userId: $request->user()->id,
            content: $request->validated('content')
        );

        $comment = $this->addCommentHandler->handle($dto);

        // Buscar o comentário criado
        $eloquentComment = EloquentComment::with(['user', 'task'])
            ->find($comment->id->value);

        return response()->json(
            new CommentResource($eloquentComment),
            201
        );
    }

    /**
     * Remove the specified comment.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $comment = EloquentComment::with(['task.project'])->findOrFail($id);

        // Verificar se o usuário é o autor do comentário ou membro do projeto
        if ($comment->user_id !== $request->user()->id 
            && !$comment->task->project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        $comment->delete();

        return response()->json(null, 204);
    }
}
