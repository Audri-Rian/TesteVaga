<?php

namespace Src\Domain\Comment\Contracts;

use Src\Domain\Comment\Comment;
use Src\Domain\Comment\CommentId;

interface CommentRepository
{
    public function save(Comment $comment): void;
    public function getById(CommentId $id): ?Comment;
    public function delete(CommentId $id): void;
}
