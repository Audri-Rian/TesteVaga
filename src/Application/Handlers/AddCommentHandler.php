<?php

namespace Src\Application\Handlers;

use Src\Application\DTOs\AddCommentDTO;
use Src\Domain\Comment\Comment;
use Src\Domain\Comment\CommentId;
use Src\Domain\Comment\Contracts\CommentRepository;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\TaskId;

final class AddCommentHandler
{
    public function __construct(
        private readonly CommentRepository $commentRepository
    ) {}

    public function handle(AddCommentDTO $dto): Comment
    {
        // 1. Criar IDs
        $commentId = CommentId::fromString(\Illuminate\Support\Str::uuid()->toString());
        $taskId = TaskId::fromString($dto->taskId);
        $authorId = UserId::fromString($dto->userId);

        // 2. Criar Comment entity
        $comment = Comment::create($commentId, $taskId, $authorId, $dto->content);

        // 3. Persistir
        $this->commentRepository->save($comment);

        return $comment;
    }
}
