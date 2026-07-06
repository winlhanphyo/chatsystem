<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('chat'));

Route::middleware(['auth', 'verified', UpdateLastSeen::class])->group(function () {
    // Chat main page
    Route::get('/chat', [ChatController::class, 'index'])->name('chat');
    Route::get('/chat/users', [ChatController::class, 'users'])->name('chat.users');

    // Conversations
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/read', [ConversationController::class, 'markRead'])->name('conversations.read');

    // Messages
    Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])
        ->middleware('throttle:60,1')
        ->name('messages.store');
    Route::post('/conversations/{conversation}/typing', [MessageController::class, 'typing'])->name('messages.typing');

    // Profile (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
