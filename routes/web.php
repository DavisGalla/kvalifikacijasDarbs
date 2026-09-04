<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PersonalBestController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/create', [CalendarController::class, 'create'])->name('calendar.create');
    Route::get('/calendar/{eventId}', [CalendarController::class, 'show'])->name('calendar.show');
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::delete('/calendar/{eventId}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    Route::get('/pbs', [PersonalBestController::class, 'index'])->name('pbs.index');
    Route::post('/pbs', [PersonalBestController::class, 'store'])->name('pbs.store');
    Route::patch('/pbs/{pb}', [PersonalBestController::class, 'update'])->name('pbs.update');
    Route::delete('/pbs/{pb}', [PersonalBestController::class, 'destroy'])->name('pbs.destroy');

    Route::get('/blog', [PostController::class, 'index'])->name('blog.index');
    Route::get('/blog/create', [PostController::class, 'create'])->name('blog.create');
    Route::post('/blog', [PostController::class, 'store'])->name('blog.store');
    Route::get('/blog/{post}', [PostController::class, 'show'])->name('blog.show');
    Route::delete('/blog/{post}', [PostController::class, 'destroy'])->name('blog.destroy');

    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
});

require __DIR__.'/auth.php';
