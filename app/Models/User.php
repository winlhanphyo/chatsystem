<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['is_online', 'avatar_url', 'initials', 'avatar_color'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class)
            ->withPivot('last_read_message_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function getIsOnlineAttribute(): bool
    {
        return $this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes(2));
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    public function getInitialsAttribute(): string
    {
        $words = explode(' ', trim($this->name));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($this->name, 0, 2));
    }

    public function getAvatarColorAttribute(): string
    {
        $colors = [
            '#4F46E5', '#7C3AED', '#DB2777', '#DC2626',
            '#D97706', '#059669', '#0284C7', '#0891B2',
        ];
        return $colors[$this->id % count($colors)];
    }
}
