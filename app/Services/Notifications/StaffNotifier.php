<?php

namespace App\Services\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Fans an in-app notification out to staff users (by role or explicit user).
 * Staff alerts are in_app-only by default; the hub still logs each per-user.
 */
class StaffNotifier
{
    /**
     * Notify every active user whose role name is in $roles.
     *
     * @param  string[]  $roles  e.g. ['admin','super_admin','secretary']
     */
    public static function toRoles(array $roles, string $eventKey, array $data = []): int
    {
        $users = User::query()
            ->where('is_active', true)
            ->whereHas('role', fn ($q) => $q->whereIn('name', $roles))
            ->get();

        foreach ($users as $user) {
            self::toUser($user, $eventKey, $data);
        }

        return $users->count();
    }

    public static function toUser(Model $user, string $eventKey, array $data = []): void
    {
        // Force in_app so staff alerts never consume SMS/WhatsApp budget unless
        // a caller explicitly routes them elsewhere.
        Notifier::event($eventKey, $user, $data, ['in_app']);
    }
}
