<?php

namespace App\Http\Controllers\Api\Chat;

use App\Http\Controllers\Controller;
use App\Http\Requests\Chat\StoreDirectConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ConversationController extends Controller
{
    public function __construct(private readonly ChatService $chatService) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $conversations = Conversation::query()
            ->whereHas('participants', fn ($query) => $query->where('users.id', $request->user()->id))
            ->with([
                'project:id,name',
                'participants:id,name,email',
                'lastMessage.sender:id,name,email',
            ])
            ->latest('updated_at')
            ->paginate($request->integer('per_page', 15));

        return ConversationResource::collection($conversations);
    }

    public function show(Request $request, Conversation $conversation): ConversationResource
    {
        abort_unless(
            $this->chatService->userCanAccessConversation($request->user(), $conversation),
            403,
            'You are not a participant in this conversation.'
        );

        $conversation->load([
            'project:id,name',
            'participants:id,name,email',
            'lastMessage.sender:id,name,email',
        ]);

        return new ConversationResource($conversation);
    }

    public function showProject(Request $request, Project $project): ConversationResource
    {
        abort_unless(
            $this->chatService->canAccessProject($request->user(), $project),
            403,
            'You do not have access to this project chat.'
        );

        $conversation = $this->chatService->findOrCreateProjectConversation($project);
        $conversation->load([
            'project:id,name',
            'participants:id,name,email',
            'lastMessage.sender:id,name,email',
        ]);

        return new ConversationResource($conversation);
    }

    public function storeDirect(StoreDirectConversationRequest $request): ConversationResource
    {
        $recipient = User::findOrFail($request->integer('user_id'));
        $conversation = $this->chatService->findOrCreateDirectConversation($request->user(), $recipient);

        $conversation->load([
            'participants:id,name,email',
            'lastMessage.sender:id,name,email',
        ]);

        return new ConversationResource($conversation);
    }
}
