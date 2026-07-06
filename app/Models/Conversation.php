<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'last_message_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('last_read_message_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_message_id');
    }

    public function getUnreadCountForUser(int $userId): int
    {
        $pivot = $this->users->firstWhere('id', $userId)?->pivot;

        $query = $this->messages()->where('user_id', '!=', $userId);

        if ($pivot?->last_read_message_id) {
            $query->where('id', '>', $pivot->last_read_message_id);
        }

        return $query->count();
    }
}
