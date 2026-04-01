<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\ChatController as BaseChatController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SecretaryChatController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return Inertia::render('Secretary/Chat/Index', [
            'conversations' => BaseChatController::getConversations($user),
            'users' => BaseChatController::getUsers($user),
            'activeConversation' => null,
            'messages' => [],
        ]);
    }

    public function show(User $user)
    {
        $currentUser = auth()->user();

        return Inertia::render('Secretary/Chat/Index', [
            'conversations' => BaseChatController::getConversations($currentUser),
            'users' => BaseChatController::getUsers($currentUser),
            'activeConversation' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'role_display' => $user->role?->display_name_en,
                'last_seen_at' => $user->last_seen_at?->toISOString(),
                'is_online' => $user->last_seen_at && $user->last_seen_at->diffInMinutes(now()) < 2,
            ],
            'messages' => BaseChatController::getMessages($currentUser, $user),
        ]);
    }

    public function store(Request $request, User $user)
    {
        $message = BaseChatController::sendMessage(auth()->user(), $user, $request);

        if ($request->wantsJson()) {
            return response()->json([
                'message' => [
                    'id' => $message->id,
                    'sender_id' => $message->sender_id,
                    'receiver_id' => $message->receiver_id,
                    'body' => $message->body,
                    'attachment_path' => $message->attachment_path,
                    'attachment_name' => $message->attachment_name,
                    'read_at' => null,
                    'created_at' => $message->created_at->toISOString(),
                    'sender_name' => auth()->user()->name,
                ],
            ]);
        }

        return redirect()->route('secretary.chat.show', $user->id);
    }

    public function poll(Request $request, User $user)
    {
        $lastMessageId = $request->query('after');

        return response()->json([
            'messages' => BaseChatController::pollMessages(auth()->user(), $user, $lastMessageId),
        ]);
    }

    public function markRead(User $user)
    {
        \App\Models\Message::where('sender_id', $user->id)
            ->where('receiver_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function unreadCount()
    {
        return response()->json(
            BaseChatController::getUnreadWithPreview(auth()->user())
        );
    }

    public function loadOlder(Request $request, User $user)
    {
        $beforeId = $request->query('before');

        return response()->json([
            'messages' => BaseChatController::loadOlderMessages(auth()->user(), $user, $beforeId),
        ]);
    }
}
