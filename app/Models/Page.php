<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\LogsActivity;

class Page extends Model
{
    use LogsActivity;
    protected $fillable = [
        'slug', 'title_ar', 'title_en',
        'content_ar', 'content_en',
        'seo_title_ar', 'seo_title_en',
        'seo_desc_ar', 'seo_desc_en',
    ];
}
