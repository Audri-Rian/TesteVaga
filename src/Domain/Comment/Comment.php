<?php

namespace Src\Domain\Comment;

use Src\Domain\Comment\Events\CommentAdded;
use Src\Domain\Shared\Event\RecordsEvents;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\TaskId;

/**
 * Entidade de Domínio Comment.
 * Representa um comentário em uma tarefa.
 */
final class Comment
{
    use RecordsEvents;

    private function __construct(
        public readonly CommentId $id,
        public readonly TaskId $taskId,
        public readonly UserId $authorId,
        public string $content,
        public readonly \DateTimeImmutable $createdAt
    ) {}

    public static function create(
        CommentId $id,
        TaskId $taskId,
        UserId $authorId,
        string $content
    ): self {
        if (trim($content) === '') {
            throw new \DomainException('Comment content cannot be empty.');
        }

        $comment = new self(
            $id,
            $taskId,
            $authorId,
            $content,
            new \DateTimeImmutable()
        );

        // Registra evento de domínio
        $comment->recordThat(new CommentAdded($id));

        return $comment;
    }

    public function update(string $newContent): void
    {
        if (trim($newContent) === '') {
            throw new \DomainException('Comment content cannot be empty.');
        }

        $this->content = $newContent;
    }
}
