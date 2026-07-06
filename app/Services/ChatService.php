<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatService
{
    /**
     * Persist a new message, update the conversation, and broadcast the event.
     */
    public function sendMessage(Conversation $conversation, User $user, array $data): Message
    {
        $attachment = null;
        $type       = $data['type'] ?? 'text';

        if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
            $attachment = $data['attachment']->store('attachments', 'public');
            $type       = $this->resolveFileType($data['attachment']);
        }

        return DB::transaction(function () use ($conversation, $user, $data, $attachment, $type) {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'user_id'         => $user->id,
                'message'         => $data['message'] ?? null,
                'type'            => $type,
                'attachment'      => $attachment,
            ]);

            $conversation->update([
                'last_message_id' => $message->id,
                'last_message_at' => $message->created_at,
            ]);

            broadcast(new MessageSent($message))->toOthers();

            return $message;
        });
    }

    /**
     * Delete a stored attachment file.
     */
    public function deleteAttachment(string $path): void
    {
        Storage::disk('public')->delete($path);
    }

    private function resolveFileType(UploadedFile $file): string
    {
        return str_starts_with($file->getMimeType(), 'image/') ? 'image' : 'file';
    }
}
