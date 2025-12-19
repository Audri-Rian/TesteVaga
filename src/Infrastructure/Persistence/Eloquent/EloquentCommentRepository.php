<?php

namespace Src\Infrastructure\Persistence\Eloquent;

use App\Models\Comment as CommentModel;
use ReflectionClass;
use Src\Domain\Comment\Comment;
use Src\Domain\Comment\CommentId;
use Src\Domain\Comment\Contracts\CommentRepository;
use Src\Domain\Shared\UserId;
use Src\Domain\Task\TaskId;

class EloquentCommentRepository implements CommentRepository
{
    public function save(Comment $comment): void
    {
        CommentModel::updateOrCreate(
            ['id' => $comment->id->value],
            [
                'task_id' => $comment->taskId->value,
                'user_id' => $comment->userId->value,
                'content' => $comment->content,
            ]
        );
    }

    public function getById(CommentId $id): ?Comment
    {
        $model = CommentModel::find($id->value);

        if (! $model) {
            return null;
        }

        return $this->hydrateComment($model);
    }

    public function delete(CommentId $id): void
    {
        CommentModel::destroy($id->value);
    }

    private function hydrateComment(CommentModel $model): Comment
    {
        $reflection = new ReflectionClass(Comment::class);
        $comment = $reflection->newInstanceWithoutConstructor();

        // id
        $p = $reflection->getProperty('id');
        $p->setAccessible(true);
        $p->setValue($comment, CommentId::fromString($model->id));

        // taskId
        $p = $reflection->getProperty('taskId');
        $p->setAccessible(true);
        $p->setValue($comment, TaskId::fromString($model->task_id));

        // userId
        $p = $reflection->getProperty('userId');
        $p->setAccessible(true);
        $p->setValue($comment, UserId::fromString((string)$model->user_id));

        // content
        $p = $reflection->getProperty('content');
        $p->setAccessible(true);
        $p->setValue($comment, $model->content);

        return $comment;
    }
}
