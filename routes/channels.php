<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

// Breeze default – user model notifications
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel per conversation – only participants may join
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return $user->conversations()
        ->where('conversations.id', (int) $conversationId)
        ->exists();
});

// Presence channel for global online status
Broadcast::channel('online', function ($user) {
    return [
        'id'          => $user->id,
        'name'        => $user->name,
        'avatar_url'  => $user->avatar_url,
        'initials'    => $user->initials,
        'avatar_color'=> $user->avatar_color,
    ];
});
