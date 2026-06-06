<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Validate that the target user is a valid chat recipient.
     * Prevents messaging deactivated users or yourself.
     */
    public static function validateChatAccess(User $currentUser, User $targetUser): void
    {
        // Prevent self-messaging
        if ($currentUser->id === $targetUser->id) {
            abort(403, 'Cannot send messages to yourself.');
        }

        // Target must be an active user
        if (method_exists($targetUser, 'scopeActive')) {
            $isActive = User::active()->where('id', $targetUser->id)->exists();
            if (! $isActive) {
                abort(403, 'This user is no longer active.');
            }
        }

        // Target must have a role assigned
        if (! $targetUser->role) {
            abort(403, 'Cannot message this user.');
        }
    }

    /**
     * Get conversations list for a user — each conversation is the other user
     * with their last message preview and unread count.
     */
    public static function getConversations(User $user): array
    {
        $userId = $user->id;

        // Get distinct user IDs that the current user has chatted with
        $contactIds = Message::where(function ($q) use ($userId) {
            $q->where('sender_id', $userId)
                ->orWhere('receiver_id', $userId);
        })
            ->selectRaw('CASE WHEN sender_id = ? THEN receiver_id ELSE sender_id END as contact_id', [$userId])
            ->distinct()
            ->pluck('contact_id');

        $conversations = [];

        foreach ($contactIds as $contactId) {
            $contact = User::with('role')->find($contactId);
            if (! $contact) {
                continue;
            }

            // Last message in conversation
            $lastMessage = Message::conversation($userId, $contactId)
                ->latest()
                ->first();

            // Unread count (messages FROM this contact TO current user)
            $unreadCount = Message::where('sender_id', $contactId)
                ->where('receiver_id', $userId)
                ->whereNull('read_at')
                ->count();

            $conversations[] = [
                'user' => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'email' => $contact->email,
                    'role' => $contact->role?->name,
                    'role_display' => $contact->role?->display_name_en,
                    'last_seen_at' => $contact->last_seen_at?->toISOString(),
                    'is_online' => $contact->last_seen_at && $contact->last_seen_at->diffInMinutes(now()) < 2,
                ],
                'last_message' => $lastMessage ? [
                    'id' => $lastMessage->id,
                    'body' => $lastMessage->body,
                    'attachment_name' => $lastMessage->attachment_name,
                    'sender_id' => $lastMessage->sender_id,
                    'created_at' => $lastMessage->created_at->toISOString(),
                    'is_read' => $lastMessage->read_at !== null,
                ] : null,
                'unread_count' => $unreadCount,
            ];
        }

        // Sort by last message time (most recent first)
        usort($conversations, function ($a, $b) {
            $timeA = $a['last_message']['created_at'] ?? '1970-01-01';
            $timeB = $b['last_message']['created_at'] ?? '1970-01-01';

            return strcmp($timeB, $timeA);
        });

        return $conversations;
    }

    /**
     * Get messages between two users (latest 50).
     */
    public static function getMessages(User $user, User $otherUser): array
    {
        // Validate chat access
        static::validateChatAccess($user, $otherUser);

        $messages = Message::conversation($user->id, $otherUser->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        // Mark messages from the other user as read
        Message::where('sender_id', $otherUser->id)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,
                'body' => $msg->body,
                'attachment_path' => $msg->attachment_path,
                'attachment_url' => \App\Support\SecureMedia::url($msg->attachment_path),
                'attachment_name' => $msg->attachment_name,
                'read_at' => $msg->read_at?->toISOString(),
                'created_at' => $msg->created_at->toISOString(),
                'sender_name' => $msg->sender?->name,
            ];
        })->toArray();
    }

    /**
     * Send a message to another user.
     */
    public static function sendMessage(User $sender, User $receiver, Request $request): Message
    {
        // Validate chat access (active user, valid role, not self)
        static::validateChatAccess($sender, $receiver);

        $request->validate([
            'body' => 'nullable|string|max:5000',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip,rar|max:10240', // 10MB max, safe types only
        ]);

        // Ensure at least body or attachment is provided
        if (! $request->filled('body') && ! $request->hasFile('attachment')) {
            abort(422, 'Message body or attachment is required.');
        }

        $data = [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'body' => $request->input('body'),
        ];

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $data['attachment_path'] = $file->store('uploads/messages', 'local');
            // Sanitize original filename to prevent path traversal / XSS in file names
            $data['attachment_name'] = preg_replace('/[^\w\s\-\.]/', '_', basename($file->getClientOriginalName()));
        }

        return Message::create($data);
    }

    /**
     * Get all active users except the current user.
     */
    public static function getUsers(User $currentUser): array
    {
        return User::active()
            ->where('id', '!=', $currentUser->id)
            ->whereHas('role')
            ->with('role')
            ->get()
            ->map(function ($u) {
                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role?->name,
                    'role_display' => $u->role?->display_name_en,
                    'last_seen_at' => $u->last_seen_at?->toISOString(),
                    'is_online' => $u->last_seen_at && $u->last_seen_at->diffInMinutes(now()) < 2,
                ];
            })
            ->toArray();
    }

    /**
     * Get unread message count for a user.
     */
    public static function getUnreadCount(User $user): int
    {
        return Message::unreadFor($user->id)->count();
    }

    /**
     * Get unread count with latest message preview (for toast notifications).
     */
    public static function getUnreadWithPreview(User $user): array
    {
        $count = Message::unreadFor($user->id)->count();

        $latest = null;
        if ($count > 0) {
            $latestMsg = Message::unreadFor($user->id)
                ->with('sender:id,name')
                ->latest()
                ->first();

            if ($latestMsg) {
                $latest = [
                    'id' => $latestMsg->id,
                    'sender_id' => $latestMsg->sender_id,
                    'sender_name' => $latestMsg->sender?->name,
                    'body' => $latestMsg->body ? mb_substr($latestMsg->body, 0, 80) : null,
                    'has_attachment' => ! empty($latestMsg->attachment_name),
                    'created_at' => $latestMsg->created_at->toISOString(),
                ];
            }
        }

        return [
            'unread_count' => $count,
            'latest_message' => $latest,
        ];
    }

    /**
     * Poll for new messages after a given message ID.
     */
    public static function pollMessages(User $user, User $otherUser, $lastMessageId = null): array
    {
        $query = Message::conversation($user->id, $otherUser->id);

        $lastId = $lastMessageId ? (int) $lastMessageId : null;
        if ($lastId) {
            $query->where('id', '>', $lastId);
        }

        $messages = $query->with(['sender:id,name'])
            ->orderBy('id', 'asc')
            ->get();

        // Mark messages from the other user as read
        Message::where('sender_id', $otherUser->id)
            ->where('receiver_id', $user->id)
            ->whereNull('read_at')
            ->whereIn('id', $messages->pluck('id'))
            ->update(['read_at' => now()]);

        return $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,
                'body' => $msg->body,
                'attachment_path' => $msg->attachment_path,
                'attachment_url' => \App\Support\SecureMedia::url($msg->attachment_path),
                'attachment_name' => $msg->attachment_name,
                'read_at' => $msg->read_at?->toISOString(),
                'created_at' => $msg->created_at->toISOString(),
                'sender_name' => $msg->sender?->name,
            ];
        })->toArray();
    }

    /**
     * Delete entire conversation between two users.
     * Removes all messages and their attachments.
     */
    public static function deleteConversation(User $user, User $otherUser): int
    {
        // Get messages with attachments to clean up files
        $messagesWithAttachments = Message::conversation($user->id, $otherUser->id)
            ->whereNotNull('attachment_path')
            ->pluck('attachment_path');

        // Delete attachment files from storage
        foreach ($messagesWithAttachments as $path) {
            \App\Support\SecureMedia::delete($path);
        }

        // Delete all messages in the conversation
        $count = Message::conversation($user->id, $otherUser->id)->delete();

        return $count;
    }

    /**
     * Load older messages before a given message ID.
     */
    public static function loadOlderMessages(User $user, User $otherUser, $beforeId): array
    {
        $id = (int) $beforeId;
        if (! $id) {
            return [];
        }

        $messages = Message::conversation($user->id, $otherUser->id)
            ->where('id', '<', $id)
            ->with(['sender:id,name'])
            ->latest()
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(function ($msg) {
            return [
                'id' => $msg->id,
                'sender_id' => $msg->sender_id,
                'receiver_id' => $msg->receiver_id,
                'body' => $msg->body,
                'attachment_path' => $msg->attachment_path,
                'attachment_url' => \App\Support\SecureMedia::url($msg->attachment_path),
                'attachment_name' => $msg->attachment_name,
                'read_at' => $msg->read_at?->toISOString(),
                'created_at' => $msg->created_at->toISOString(),
                'sender_name' => $msg->sender?->name,
            ];
        })->toArray();
    }
}
