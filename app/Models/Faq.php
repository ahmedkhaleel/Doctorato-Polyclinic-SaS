<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Faq extends Model
{
    use LogsActivity;
    protected $fillable = [
        'category', 'question_ar', 'question_en',
        'answer_ar', 'answer_en', 'display_order',
    ];
}
