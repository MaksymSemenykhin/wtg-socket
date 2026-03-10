<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Collection;

class MessageService
{
    /**
     * Get all users except the given one, with unread message count for the current user.
     *
     * @return Collection<int, User>
     */
    public function getUsersWithUnreadCounts(int $userId): Collection
    {
        return User::query()
            ->where('id', '!=', $userId)
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($userId) {
                $user->unread_count = Message::query()
                    ->where('recipient_id', $userId)
                    ->where('sender_id', $user->id)
                    ->whereNull('read_at')
                    ->count();

                return $user;
            });
    }

    /**
     * Create a message, broadcast it, and return the loaded message.
     *
     * @throws \InvalidArgumentException when sender and recipient are the same
     */
    public function sendMessage(int $senderId, int $recipientId, string $body): Message
    {
        if ($senderId === $recipientId) {
            throw new \InvalidArgumentException(__('messages.cannot_send_to_self'));
        }

        $message = Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'body' => $body,
        ]);

        $message->load(['sender', 'recipient']);
        MessageSent::dispatch($message);

        return $message;
    }

    /**
     * Mark messages from the other user as read, then return the conversation and current unread counts.
     *
     * @return array{ messages: Collection, unread_counts: array<int, int> }
     */
    public function getConversationWithUnreadCounts(int $userId, int $otherUserId): array
    {
        if ($userId === $otherUserId) {
            throw new \InvalidArgumentException(__('messages.cannot_load_conversation_self'));
        }

        Message::query()
            ->where('recipient_id', $userId)
            ->where('sender_id', $otherUserId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = Message::query()
            ->where(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $userId)->where('recipient_id', $otherUserId);
            })
            ->orWhere(function ($q) use ($userId, $otherUserId) {
                $q->where('sender_id', $otherUserId)->where('recipient_id', $userId);
            })
            ->with(['sender', 'recipient'])
            ->orderBy('created_at')
            ->get();

        $unreadCounts = Message::query()
            ->where('recipient_id', $userId)
            ->whereNull('read_at')
            ->selectRaw('sender_id, count(*) as c')
            ->groupBy('sender_id')
            ->pluck('c', 'sender_id')
            ->all();

        return [
            'messages' => $messages,
            'unread_counts' => $unreadCounts,
        ];
    }

    /**
     * Format a single message for JSON response.
     *
     * @return array<string, mixed>
     */
    public function formatMessageForJson(Message $message): array
    {
        $message->loadMissing(['sender', 'recipient']);

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender' => [
                'id' => $message->sender->id,
                'name' => $message->sender->name,
                'email' => $message->sender->email,
            ],
            'recipient_id' => $message->recipient_id,
            'recipient' => [
                'id' => $message->recipient->id,
                'name' => $message->recipient->name,
                'email' => $message->recipient->email,
            ],
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * Format a message model for JSON (for list items).
     *
     * @return array<string, mixed>
     */
    public function formatMessageItem(Message $m): array
    {
        return [
            'id' => $m->id,
            'sender_id' => $m->sender_id,
            'sender' => [
                'id' => $m->sender->id,
                'name' => $m->sender->name,
                'email' => $m->sender->email,
            ],
            'recipient_id' => $m->recipient_id,
            'recipient' => [
                'id' => $m->recipient->id,
                'name' => $m->recipient->name,
                'email' => $m->recipient->email,
            ],
            'body' => $m->body,
            'created_at' => $m->created_at->toIso8601String(),
        ];
    }
}
