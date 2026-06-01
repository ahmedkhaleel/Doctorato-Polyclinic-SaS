<?php

namespace App\Models;

use App\Models\Concerns\StampsBranch;
use Illuminate\Database\Eloquent\Model;

class AiRequestLog extends Model
{
    use StampsBranch;

    protected $fillable = [
        'branch_id', 'feature', 'provider', 'model', 'actor_type', 'actor_id',
        'prompt_tokens', 'completion_tokens', 'cost_usd', 'latency_ms',
        'status', 'error', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
        'cost_usd' => 'decimal:5',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'latency_ms' => 'integer',
    ];
}
