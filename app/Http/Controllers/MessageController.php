<?php

namespace App\Http\Controllers;

use App\Events\UserTyping;
use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    /**
     * Return messages for a conversation using cursor pagination (newest first).
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        $messages = $conversation->messages()
            ->with('user:id,name,avatar,last_seen_at')
            ->latest()
            ->cursorPaginate(30);

        return response()->json([
            'messages'    => collect($messages->items())->map(fn ($m) => $this->formatMessage($m)),
            'next_cursor' => $messages->nextCursor()?->encode(),
            'has_more'    => $messages->hasMorePages(),
        ]);
    }

    /**
     * Store a new message (text or with attachment).
     */
    public function store(StoreMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('sendMessage', $conversation);

        $message = $this->chatService->sendMessage($conversation, $request->user(), $request->validated());
        $message->load('user:id,name,avatar,last_seen_at');

        return response()->json(['message' => $this->formatMessage($message)], 201);
    }

    /**
     * Broadcast a typing event to other participants.
     */
    public function typing(Request $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        broadcast(new UserTyping(
            $request->user(),
            $conversation->id,
            $request->boolean('is_typing', true)
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    private function formatMessage($message): array
    {
        return [
            'id'             => $message->id,
            'conversation_id'=> $message->conversation_id,
            'user_id'        => $message->user_id,
            'message'        => $message->message,
            'type'           => $message->type,
            'attachment'     => $message->attachment,
            'attachment_url' => $message->attachment_url,
            'created_at'     => $message->created_at->toISOString(),
            'formatted_time' => $message->formatted_time,
            'user' => $message->user ? [
                'id'          => $message->user->id,
                'name'        => $message->user->name,
                'avatar_url'  => $message->user->avatar_url,
                'initials'    => $message->user->initials,
                'avatar_color'=> $message->user->avatar_color,
            ] : null,
        ];
    }
}
