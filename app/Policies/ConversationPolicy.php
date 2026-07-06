<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users->contains('id', $user->id);
    }

    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->users->contains('id', $user->id);
    }

    public function markRead(User $user, Conversation $conversation): bool
    {
        return $conversation->users->contains('id', $user->id);
    }
}
