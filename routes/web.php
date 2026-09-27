<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
    Route::get('matches/{gameMatch}', [GameController::class, 'show'])->name('matches.show');

    Route::prefix('matches/{gameMatch}')->name('matches.')->group(function () {
        Route::get('state', [GameController::class, 'state'])->name('state');
        Route::post('movement-roll', [GameController::class, 'rollMovement'])->name('movement-roll');
        Route::post('move', [GameController::class, 'move'])->name('move');
        Route::post('attack', [GameController::class, 'attack'])->name('attack');
    });
});

require __DIR__.'/settings.php';
