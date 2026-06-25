<?php

use App\Models\Conversation;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return (int) $user->id === $id;
});

Broadcast::channel('conversation.{conversationId}', function (User $user, int $conversationId): bool {
    $conversation = Conversation::query()->find($conversationId);

    if ($conversation === null) {
        return false;
    }

    return app(ChatService::class)->userCanAccessConversation($user, $conversation);
});
