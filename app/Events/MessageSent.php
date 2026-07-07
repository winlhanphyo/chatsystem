<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        $this->message->load(['user', 'conversation.users']);
    }

    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];

        foreach ($this->message->conversation->users as $recipient) {
            if ($recipient->id !== $this->message->user_id) {
                $channels[] = new PrivateChannel('App.Models.User.' . $recipient->id);
            }
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        $user = $this->message->user;

        return [
            'message' => [
                'id'             => $this->message->id,
                'conversation_id'=> $this->message->conversation_id,
                'user_id'        => $this->message->user_id,
                'message'        => $this->message->message,
                'type'           => $this->message->type,
                'attachment'     => $this->message->attachment,
                'attachment_url' => $this->message->attachment_url,
                'created_at'     => $this->message->created_at->toISOString(),
                'formatted_time' => $this->message->formatted_time,
                'user' => [
                    'id'          => $user->id,
                    'name'        => $user->name,
                    'avatar_url'  => $user->avatar_url,
                    'initials'    => $user->initials,
                    'avatar_color'=> $user->avatar_color,
                ],
            ],
            'conversation' => [
                'id'              => $this->message->conversation_id,
                'last_message_at' => $this->message->created_at->toISOString(),
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
