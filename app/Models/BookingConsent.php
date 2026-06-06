<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBranch;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingConsent extends Model
{
    use BelongsToBranch;
    use HasFactory, LogsActivity;

    protected $fillable = [
        'booking_id',
        'file_path',
        'original_name',
        'uploaded_by',
    ];

    protected $appends = ['file_url'];

    // Signed, authenticated media URL (PHI — no longer a public /storage link).
    protected function fileUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => \App\Support\SecureMedia::url($this->file_path),
        );
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
