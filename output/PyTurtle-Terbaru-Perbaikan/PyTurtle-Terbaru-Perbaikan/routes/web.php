<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CodeMatchController;
use App\Http\Controllers\GameProgressController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\MazeController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ProgressController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect(auth()->check() ? '/home' : '/login'));
Route::middleware('guest')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::view('/register', 'auth.register')->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::view('/forgot-password', 'auth.forgot-password')->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token) => view('auth.reset-password', compact('token')))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/home', HomeController::class)->name('home');
    Route::get('/modules', [ModuleController::class, 'index'])->name('modules');
    Route::get('/modules/{module}', [ModuleController::class, 'show'])->name('modules.show');
    Route::post('/modules/{module}/complete', [ModuleController::class, 'complete']);
    Route::get('/maze', [MazeController::class, 'index'])->name('maze');
    Route::get('/maze/{level}', [MazeController::class, 'show'])->name('maze.show');
    Route::get('/game/continue', [MazeController::class, 'resume'])->name('game.continue');
    Route::post('/game/progress/save', [GameProgressController::class, 'save'])->middleware('throttle:120,1');
    Route::post('/game/level/complete', [GameProgressController::class, 'save'])->middleware('throttle:120,1');
    Route::post('/game/{level}/hint', [GameProgressController::class, 'hint'])->middleware('throttle:30,1');
    Route::post('/game/story', [GameProgressController::class, 'story'])->middleware('throttle:60,1');
    Route::post('/game/restart', [GameProgressController::class, 'restart']);
    Route::get('/code-match', [CodeMatchController::class, 'show'])->name('code-match');
    Route::get('/code-match/{lesson}', [CodeMatchController::class, 'show'])->where('lesson', '4');
    Route::post('/code-match/submit', [CodeMatchController::class, 'submit'])->middleware('throttle:30,1');
    Route::get('/progress', ProgressController::class)->name('progress');
    Route::get('/leaderboard', LeaderboardController::class)->name('leaderboard');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingsController::class, 'update']);
    Route::get('/profile', [SettingsController::class, 'profile'])->name('profile');
    Route::post('/profile',[SettingsController::class, 'updateProfile']);
});
