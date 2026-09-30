<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\RoomController;
use App\Http\Middleware\AdminOnly;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

// Standalone WebSocket game & chat prototype
Route::get('/game', [GameController::class, 'index'])->name('game');
Route::post('/game/move', [GameController::class, 'move'])->name('game.move');
Route::post('/chat/send', [GameController::class, 'chat'])->name('game.chat');
Route::post('/game/join', [GameController::class, 'join'])->name('game.join');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/lobby', [LobbyController::class, 'index'])->name('lobby');
    Route::post('/character/reroll', [LobbyController::class, 'reroll'])->name('character.reroll');
    Route::get('/rooms/create', [LobbyController::class, 'create'])->name('rooms.create');
    Route::post('/rooms', [LobbyController::class, 'store'])->name('rooms.store');
    Route::post('/rooms/{room}/join', [LobbyController::class, 'join'])->name('rooms.join');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

    Route::prefix('/rooms/{room}')->group(function () {
        Route::get('/state', [RoomController::class, 'state']);
        Route::post('/ready', [RoomController::class, 'ready']);
        Route::post('/start', [RoomController::class, 'start']);
        Route::post('/end-match', [RoomController::class, 'endMatch']);
        Route::post('/chat', [RoomController::class, 'chat']);
        Route::post('/roll', [RoomController::class, 'roll']);
        Route::post('/move', [RoomController::class, 'move']);
        Route::post('/attack', [RoomController::class, 'attack']);
        Route::post('/item', [RoomController::class, 'item']);
        Route::post('/end', [RoomController::class, 'end']);
        Route::post('/leave', [RoomController::class, 'leave']);
    });
});

Route::middleware(['auth', AdminOnly::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::put('/users/{id}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::delete('/users/{id}', [AdminController::class, 'deleteUser'])->name('users.delete');
    Route::post('/users/{id}/restore', [AdminController::class, 'restoreUser'])->name('users.restore');
    Route::get('/items', [AdminController::class, 'items'])->name('items');
    Route::post('/items', [AdminController::class, 'storeItem'])->name('items.store');
    Route::put('/items/{item}', [AdminController::class, 'updateItem'])->name('items.update');
    Route::delete('/items/{item}', [AdminController::class, 'deleteItem'])->name('items.delete');
    Route::get('/games', [AdminController::class, 'games'])->name('games');
    Route::get('/games/{match}', [AdminController::class, 'game'])->name('games.show');
});
