<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\LogsActivity;

class PostCategory extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name_ar', 'name_en', 'slug', 'description_ar', 'description_en',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'category_id');
    }
}
