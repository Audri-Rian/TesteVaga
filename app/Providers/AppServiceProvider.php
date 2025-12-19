<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \Src\Domain\Project\Contracts\ProjectRepository::class,
            \Src\Infrastructure\Persistence\Eloquent\EloquentProjectRepository::class
        );

        $this->app->bind(
            \Src\Domain\Task\Contracts\TaskRepository::class,
            \Src\Infrastructure\Persistence\Eloquent\EloquentTaskRepository::class
        );

        $this->app->bind(
            \Src\Domain\Comment\Contracts\CommentRepository::class,
            \Src\Infrastructure\Persistence\Eloquent\EloquentCommentRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
