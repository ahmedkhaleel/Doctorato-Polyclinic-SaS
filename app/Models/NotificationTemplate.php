<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    protected $fillable = ['event_key', 'channel', 'subject', 'body_ar', 'body_en', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** Render the localized body with {{var}} substitution. */
    public function render(string $locale, array $data): string
    {
        $body = ($locale === 'ar' ? $this->body_ar : $this->body_en) ?: ($this->body_ar ?: $this->body_en ?: '');
        foreach ($data as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $body = str_replace('{{'.$key.'}}', (string) $value, $body);
            }
        }

        return $body;
    }
}
