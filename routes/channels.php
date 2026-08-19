<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channel Authorisation
|--------------------------------------------------------------------------
|
| Private-channel auth endpoint: POST /broadcasting/auth  (Sanctum guards it)
|
| Channels:
|   private-conversation.{id}  — participants only (starter or recipient)
|   private-user.{id}          — the owner of that user ID (cross-device sync)
|
*/

// Per-conversation channel: only the two participants may subscribe.
Broadcast::channel('conversation.{conversationId}', function ($user, int $conversationId): bool {
    $conversation = Conversation::query()->findOrFail($conversationId);

    if (! $conversation) {
        return false;
    }

    return in_array($user->id, [$conversation->starter_id, $conversation->recipient_id], true);
});

// Per-user channel: only the user themselves (for new-conversation notifications).
Broadcast::channel('user.{userId}', function ($user, int $userId): bool {
    return $user->id === $userId;
});
