<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class Message extends Model
{
    use LogsActivity;
    protected $fillable = [
        'sender_id',
        'receiver_id',
        'body',
        'attachment_path',
        'attachment_name',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    public function scopeUnreadFor(Builder $query, int $userId): Builder
    {
        return $query->where('receiver_id', $userId)->whereNull('read_at');
    }

    public function scopeConversation(Builder $query, int $userA, int $userB): Builder
    {
        return $query->where(function ($outer) use ($userA, $userB) {
            $outer->where(function ($q) use ($userA, $userB) {
                $q->where('sender_id', $userA)->where('receiver_id', $userB);
            })->orWhere(function ($q) use ($userA, $userB) {
                $q->where('sender_id', $userB)->where('receiver_id', $userA);
            });
        });
    }
}
