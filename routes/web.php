<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\PersonalBestController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CompetitionController;
use App\Http\Controllers\CompetitionOfficialController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\TeamController;

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

    Route::get('/competitions', [CompetitionController::class, 'index'])->name('competitions.index');
    Route::get('/competitions/history', [CompetitionController::class, 'history'])->name('competitions.history');
    Route::get('/competitions/create', [CompetitionController::class, 'create'])->name('competitions.create');
    Route::post('/competitions', [CompetitionController::class, 'store'])->name('competitions.store');
    Route::get('/competitions/{competition}', [CompetitionController::class, 'show'])->name('competitions.show');
    Route::post('/competitions/{competition}/register', [CompetitionController::class, 'register'])->name('competitions.register');
    Route::delete('/competitions/{competition}/register', [CompetitionController::class, 'cancelRegistration'])->name('competitions.registration.cancel');

    Route::post('/competitions/{competition}/officials', [CompetitionOfficialController::class, 'store'])->name('competitions.officials.store');
    Route::delete('/competitions/{competition}/officials/{user}', [CompetitionOfficialController::class, 'destroy'])->name('competitions.officials.destroy');

    Route::get('/competitions/{competition}/results', [ResultController::class, 'index'])->name('competitions.results.index');
    Route::post('/competitions/{competition}/results', [ResultController::class, 'store'])->name('competitions.results.store');
    Route::delete('/competitions/{competition}/results/{result}', [ResultController::class, 'destroy'])->name('competitions.results.destroy');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('/teams/{team}', [TeamController::class, 'show'])->name('teams.show');
    Route::post('/teams/{team}/join', [TeamController::class, 'join'])->name('teams.join');
    Route::delete('/teams/{team}/leave', [TeamController::class, 'leave'])->name('teams.leave');
    Route::delete('/teams/{team}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team}/invite', [TeamController::class, 'invite'])->name('teams.invite');
    Route::post('/team-invitations/{invitation}/accept', [TeamController::class, 'acceptInvitation'])->name('teams.invitations.accept');
    Route::delete('/team-invitations/{invitation}', [TeamController::class, 'declineInvitation'])->name('teams.invitations.decline');

    
});

require __DIR__.'/auth.php';
