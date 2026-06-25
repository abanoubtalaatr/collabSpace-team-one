<?php

namespace App\Http\Controllers\Api\Chat;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreMessageRequest;
use App\Http\Resources\MessageResource;
use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MessageController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function index(Request $request, Conversation $conversation): AnonymousResourceCollection
    {
        abort_unless(
            $this->chatService->userCanAccessConversation($request->user(), $conversation),
            403,
            'You are not a participant in this conversation.'
        );

        $messages = Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('sender:id,name,email')
            ->latest()
            ->paginate($request->integer('per_page', 30));

        return MessageResource::collection($messages);
    }

    public function store(StoreMessageRequest $request, Conversation $conversation): MessageResource
    {
        abort_unless(
            $this->chatService->userCanAccessConversation($request->user(), $conversation),
            403,
            'You are not a participant in this conversation.'
        );

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        $conversation->touch();
        $message->load('sender:id,name,email');

        MessageSent::dispatch($message);

        return new MessageResource($message);
    }
}
