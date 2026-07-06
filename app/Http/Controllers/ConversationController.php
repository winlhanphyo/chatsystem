<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function __construct(private ConversationService $conversationService) {}

    /**
     * List all conversations for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $conversations = $this->conversationService->getConversationsForUser($request->user());

        return response()->json(
            $conversations->map(fn ($c) => $this->format($c, $request->user()->id))
        );
    }

    /**
     * Find or create a 1-to-1 conversation with another user.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id', 'different:' . $request->user()->id],
        ]);

        $otherUser    = User::findOrFail($request->user_id);
        $conversation = $this->conversationService->getOrCreateDirectConversation($request->user(), $otherUser);

        $conversation->load([
            'users'       => fn ($q) => $q->withPivot('last_read_message_id'),
            'lastMessage',
        ]);

        return response()->json([
            'conversation' => $this->format($conversation, $request->user()->id),
        ]);
    }

    /**
     * Show a single conversation (used to refresh data).
     */
    public function show(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $conversation->load([
            'users'       => fn ($q) => $q->withPivot('last_read_message_id'),
            'lastMessage',
        ]);

        return response()->json([
            'conversation' => $this->format($conversation, $request->user()->id),
        ]);
    }

    /**
     * Mark all messages in a conversation as read.
     */
    public function markRead(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('markRead', $conversation);

        $this->conversationService->markAsRead($conversation, $request->user());

        return response()->json(['success' => true]);
    }

    private function format(Conversation $conversation, int $authUserId): array
    {
        $otherUser = $conversation->users->firstWhere('id', '!=', $authUserId);

        return [
            'id'          => $conversation->id,
            'other_user'  => $otherUser ? [
                'id'          => $otherUser->id,
                'name'        => $otherUser->name,
                'avatar_url'  => $otherUser->avatar_url,
                'initials'    => $otherUser->initials,
                'avatar_color'=> $otherUser->avatar_color,
                'is_online'   => $otherUser->is_online,
                'last_seen_at'=> $otherUser->last_seen_at?->toISOString(),
            ] : null,
            'last_message' => $conversation->lastMessage ? [
                'id'         => $conversation->lastMessage->id,
                'message'    => $conversation->lastMessage->message,
                'type'       => $conversation->lastMessage->type,
                'user_id'    => $conversation->lastMessage->user_id,
                'created_at' => $conversation->lastMessage->created_at->toISOString(),
                'formatted_time' => $conversation->lastMessage->formatted_time,
            ] : null,
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'unread_count'    => $conversation->unread_count ?? 0,
            'created_at'      => $conversation->created_at->toISOString(),
        ];
    }
}
