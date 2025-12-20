<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('projects', function () {
        return Inertia::render('projects/Index');
    })->name('projects.index');
    
    Route::get('projects/{id}', function ($id) {
        return Inertia::render('projects/Show', ['projectId' => $id]);
    })->name('projects.show');
    
    Route::get('tasks', function () {
        return Inertia::render('tasks/Board');
    })->name('tasks.board');

    Route::get('tasks/{id}', function ($id) {
        return Inertia::render('tasks/Show', ['taskId' => $id]);
    })->name('tasks.show');
});

require __DIR__.'/settings.php';
