<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConversationService
{
    /**
     * Find an existing direct 1-to-1 conversation between two users,
     * or create a new one.
     */
    public function getOrCreateDirectConversation(User $authUser, User $otherUser): Conversation
    {
        // Intersect conversation IDs shared by both users
        $authConvIds = DB::table('conversation_user')
            ->where('user_id', $authUser->id)
            ->pluck('conversation_id');

        $otherConvIds = DB::table('conversation_user')
            ->where('user_id', $otherUser->id)
            ->pluck('conversation_id');

        $sharedIds = $authConvIds->intersect($otherConvIds);

        if ($sharedIds->isNotEmpty()) {
            // Among shared conversations find one with exactly 2 participants
            $conversation = Conversation::whereIn('id', $sharedIds)
                ->withCount('users')
                ->having('users_count', 2)
                ->first();

            if ($conversation) {
                return $conversation;
            }
        }

        return DB::transaction(function () use ($authUser, $otherUser) {
            $conversation = Conversation::create(['last_message_at' => now()]);
            $conversation->users()->attach([$authUser->id, $otherUser->id]);
            return $conversation;
        });
    }

    /**
     * Return all conversations for a user, ordered by most recent message.
     * Attaches other_user, unread_count, and last_message on each item.
     */
    public function getConversationsForUser(User $user): Collection
    {
        $conversations = Conversation::whereHas(
            'users', fn ($q) => $q->where('user_id', $user->id)
        )
            ->with([
                'users'       => fn ($q) => $q->withPivot('last_read_message_id'),
                'lastMessage' => fn ($q) => $q->select('id', 'conversation_id', 'user_id', 'message', 'type', 'created_at'),
            ])
            ->orderByDesc('last_message_at')
            ->get();

        // Single query for all unread counts
        $unreadCounts = DB::table('messages')
            ->join('conversation_user', function ($join) use ($user) {
                $join->on('messages.conversation_id', '=', 'conversation_user.conversation_id')
                     ->where('conversation_user.user_id', $user->id);
            })
            ->where('messages.user_id', '!=', $user->id)
            ->whereRaw('messages.id > COALESCE(conversation_user.last_read_message_id, 0)')
            ->groupBy('messages.conversation_id')
            ->select('messages.conversation_id', DB::raw('COUNT(*) as count'))
            ->pluck('count', 'conversation_id');

        return $conversations->each(function ($conversation) use ($user, $unreadCounts) {
            $conversation->other_user   = $conversation->users->firstWhere('id', '!=', $user->id);
            $conversation->unread_count = $unreadCounts[$conversation->id] ?? 0;
        });
    }

    /**
     * Mark all unread messages in a conversation as read for the given user.
     */
    public function markAsRead(Conversation $conversation, User $user): void
    {
        $lastMessage = $conversation->messages()->latest()->first();

        if ($lastMessage) {
            $conversation->users()->updateExistingPivot($user->id, [
                'last_read_message_id' => $lastMessage->id,
            ]);
        }
    }
}
