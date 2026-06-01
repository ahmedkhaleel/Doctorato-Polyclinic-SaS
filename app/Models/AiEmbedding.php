<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiEmbedding extends Model
{
    protected $fillable = ['source', 'owner_type', 'owner_id', 'locale', 'content', 'vector', 'model'];

    protected $casts = ['vector' => 'array'];
}
