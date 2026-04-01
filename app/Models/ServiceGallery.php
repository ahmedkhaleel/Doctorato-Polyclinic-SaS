<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\LogsActivity;

class ServiceGallery extends Model
{
    use LogsActivity;
    protected $table = 'service_gallery';

    protected $fillable = [
        'service_id', 'image_path', 'caption_ar', 'caption_en', 'display_order',
    ];

    protected $appends = ['image'];

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path
                ? '/storage/' . $this->image_path
                : null,
        );
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
