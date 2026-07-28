<?php

namespace App\Services;

use App\Enums\ConversationType;
use App\Models\Conversation;
use App\Models\Project;
use App\Models\User;

class ChatService
{
    public function canAccessProject(User $user, Project $project): bool
    {
        if ($project->created_by === $user->id) {
            return true;
        }

        return $project->teams()
            ->whereHas('members', fn ($query) => $query->where('users.id', $user->id))
            ->exists();
    }

    public function usersShareTeam(User $first, User $second): bool
    {
        if ((int) $first->id === (int) $second->id) {
            return true;
        }

        return $first->teams()
            ->whereHas('members', fn ($query) => $query->where('users.id', $second->id))
            ->exists();
    }

    public function findOrCreateProjectConversation(Project $project): Conversation
    {
        $conversation = Conversation::query()
            ->where('type', ConversationType::Project)
            ->where('project_id', $project->id)
            ->first();

        if ($conversation) {
            $this->syncProjectParticipants($conversation, $project);

            return $conversation;
        }

        $conversation = Conversation::create([
            'type' => ConversationType::Project,
            'project_id' => $project->id,
        ]);

        $this->syncProjectParticipants($conversation, $project);

        return $conversation;
    }

    public function findOrCreateDirectConversation(User $user, User $recipient): Conversation
    {
        if (! $this->usersShareTeam($user, $recipient)) {
            abort(403, 'Direct chat requires both users to be in the same team.');
        }

        $conversation = Conversation::query()
            ->where('type', ConversationType::Direct)
            ->whereHas('participants', fn ($query) => $query->where('users.id', $user->id))
            ->whereHas('participants', fn ($query) => $query->where('users.id', $recipient->id))
            ->first();

        if ($conversation) {
            return $conversation;
        }

        $conversation = Conversation::create([
            'type' => ConversationType::Direct,
        ]);

        $conversation->participants()->sync([$user->id, $recipient->id]);

        return $conversation;
    }

    /**
     * @param  list<int>  $userIds
     */
    public function createProjectConversationWithMembers(Project $project, User $actor, array $userIds): Conversation
    {
        $conversation = $this->findOrCreateProjectConversation($project);

        $participantIds = collect($userIds)
            ->push($actor->id)
            ->push($project->created_by)
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $conversation->participants()->sync($participantIds);

        return $conversation;
    }

    public function userCanAccessConversation(User $user, Conversation $conversation): bool
    {
        return $conversation->participants()->where('users.id', $user->id)->exists();
    }

    private function syncProjectParticipants(Conversation $conversation, Project $project): void
    {
        $participantIds = $project->teams()
            ->with('members:id')
            ->get()
            ->flatMap(fn ($team) => $team->members->pluck('id'))
            ->push($project->created_by)
            ->unique()
            ->values()
            ->all();

        $conversation->participants()->sync($participantIds);
    }
}
