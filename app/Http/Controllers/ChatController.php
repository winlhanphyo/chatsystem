<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ConversationService $conversationService) {}

    /**
     * Render the main chat page with initial conversation list.
     */
    public function index(Request $request)
    {
        $user          = $request->user();
        $conversations = $this->conversationService->getConversationsForUser($user);

        $currentUserData = [
            'id'          => $user->id,
            'name'        => $user->name,
            'avatar_url'  => $user->avatar_url,
            'initials'    => $user->initials,
            'avatar_color'=> $user->avatar_color,
        ];

        $conversationsData = $conversations->map(fn ($c) => [
            'id'         => $c->id,
            'other_user' => $c->other_user ? [
                'id'          => $c->other_user->id,
                'name'        => $c->other_user->name,
                'avatar_url'  => $c->other_user->avatar_url,
                'initials'    => $c->other_user->initials,
                'avatar_color'=> $c->other_user->avatar_color,
                'is_online'   => $c->other_user->is_online,
                'last_seen_at'=> $c->other_user->last_seen_at?->toISOString(),
            ] : null,
            'last_message' => $c->lastMessage ? [
                'id'            => $c->lastMessage->id,
                'message'       => $c->lastMessage->message,
                'type'          => $c->lastMessage->type,
                'user_id'       => $c->lastMessage->user_id,
                'created_at'    => $c->lastMessage->created_at->toISOString(),
                'formatted_time'=> $c->lastMessage->formatted_time,
            ] : null,
            'last_message_at' => $c->last_message_at?->toISOString(),
            'unread_count'    => $c->unread_count ?? 0,
            'created_at'      => $c->created_at->toISOString(),
        ])->values();

        return view('chat.index', [
            'currentUser'      => $user,
            'currentUserData'  => $currentUserData,
            'conversationsData'=> $conversationsData,
        ]);
    }

    /**
     * Search users to start a new conversation.
     */
    public function users(Request $request): JsonResponse
    {
        $query         = $request->get('q', '');
        $currentUserId = $request->user()->id;

        $users = User::where('id', '!=', $currentUserId)
            ->when(
                $query,
                fn ($q) => $q->where('name', 'like', "%{$query}%")
                              ->orWhere('email', 'like', "%{$query}%")
            )
            ->select('id', 'name', 'email', 'avatar', 'last_seen_at')
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn ($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'email'       => $u->email,
                'avatar_url'  => $u->avatar_url,
                'initials'    => $u->initials,
                'avatar_color'=> $u->avatar_color,
                'is_online'   => $u->is_online,
            ]);

        return response()->json($users);
    }
}
