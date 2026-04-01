<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class ServiceFaq extends Model
{
    use LogsActivity;
    protected $fillable = [
        'service_id', 'question_ar', 'question_en',
        'answer_ar', 'answer_en', 'display_order',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
