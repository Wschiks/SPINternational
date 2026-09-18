<?php

use App\Http\Controllers\GameController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [GameController::class, 'play'])->name('dashboard');
    Route::get('/game', [GameController::class, 'play'])->name('game.play');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/game/start', [GameController::class, 'start'])->name('game.start');
    Route::get('/game/{session}/state', [GameController::class, 'state'])->name('game.state');
    Route::post('/game/{session}/level', [GameController::class, 'setLevel'])->name('game.level');
    Route::post('/game/{session}/spin', [GameController::class, 'spin'])->name('game.spin');
    Route::post('/game/{session}/hold', [GameController::class, 'hold'])->name('game.hold');
    Route::post('/game/{session}/unhold', [GameController::class, 'unhold'])->name('game.unhold');
    Route::post('/game/{session}/answer', [GameController::class, 'answer'])->name('game.answer');
    Route::post('/game/{session}/skip', [GameController::class, 'skip'])->name('game.skip');
    Route::post('/game/{session}/reject', [GameController::class, 'reject'])->name('game.reject');
    Route::post('/game/{session}/led-krans/stop', [GameController::class, 'ledKransStop'])->name('game.led-krans.stop');
    Route::post('/game/{session}/theme/select', [GameController::class, 'themeSelect'])->name('game.theme.select');
    Route::post('/game/{session}/theme-answer', [GameController::class, 'themeAnswer'])->name('game.theme-answer');
});

require __DIR__.'/auth.php';
