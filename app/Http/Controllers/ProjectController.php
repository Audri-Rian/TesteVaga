<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project as EloquentProject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Src\Application\DTOs\CreateProjectDTO;
use Src\Application\Handlers\CreateProjectHandler;

class ProjectController extends Controller
{
    public function __construct(
        private readonly CreateProjectHandler $createProjectHandler
    ) {}

    /**
     * Display a listing of projects for the authenticated user.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = EloquentProject::whereHas('members', function ($query) use ($request) {
            $query->where('user_id', $request->user()->id);
        })
            ->withCount(['tasks', 'members'])
            ->latest()
            ->get();

        return ProjectResource::collection($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(CreateProjectRequest $request): JsonResponse
    {
        $dto = new CreateProjectDTO(
            name: $request->validated('name'),
            ownerId: $request->user()->id
        );

        $project = $this->createProjectHandler->handle($dto);

        // Carregar o modelo Eloquent para retornar com relacionamentos
        $eloquentProject = EloquentProject::with(['members', 'tasks'])->find($project->id->value);

        return response()->json(
            new ProjectResource($eloquentProject),
            201
        );
    }

    /**
     * Display the specified project.
     */
    public function show(Request $request, string $id): ProjectResource
    {
        $project = EloquentProject::with(['members', 'tasks.assignee'])
            ->findOrFail($id);

        // Verificar se o usuário é membro do projeto
        if (!$project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        return new ProjectResource($project);
    }

    /**
     * Add a member to the project.
     */
    public function addMember(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'uuid', 'exists:users,id'],
        ]);

        $project = EloquentProject::findOrFail($id);

        // Verificar se o usuário atual é membro do projeto
        if (!$project->members->contains($request->user())) {
            abort(403, 'Unauthorized');
        }

        // Adicionar membro se ainda não for membro
        if (!$project->members->contains($request->input('user_id'))) {
            $project->members()->attach($request->input('user_id'), [
                'joined_at' => now(),
            ]);
        }

        $project->load('members');

        return response()->json(
            new ProjectResource($project),
            200
        );
    }
}
