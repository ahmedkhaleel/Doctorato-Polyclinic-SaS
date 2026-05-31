<?php

namespace App\Models;

use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    use LogsActivity;

    protected $fillable = [
        'name', 'email', 'phone', 'subject', 'message',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Alert front-desk staff (in-app, via the unified hub) on a new message.
        static::created(function (self $msg) {
            $preview = trim(($msg->name ? $msg->name.': ' : '').(string) str($msg->message ?? $msg->subject ?? '')->limit(70));

            \App\Services\Notifications\StaffNotifier::toRoles(['secretary', 'admin', 'super_admin'], 'staff.new_message', [
                'title' => 'رسالة تواصل جديدة',
                'body' => $preview !== '' ? $preview : 'رسالة جديدة عبر نموذج التواصل',
                'url' => "/admin/contact-messages/{$msg->id}",
                'meta' => ['title' => 'رسالة تواصل جديدة', 'body' => $preview, 'url' => "/admin/contact-messages/{$msg->id}", 'message_id' => $msg->id],
            ]);
        });
    }
}
