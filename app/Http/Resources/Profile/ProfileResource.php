<?php

namespace App\Http\Resources\Profile;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'full_phone' => $this->country_code && $this->phone
                ? $this->country_code.$this->phone
                : $this->phone,
            'about' => $this->about,
            'job_title' => $this->job_title,
            'experience_years' => $this->exp,
            'availability_status' => $this->availability_status,
            'current_team' => $this->whenLoaded('currentTeam', fn () => [
                'id' => $this->currentTeam->id,
                'name' => $this->currentTeam->name,
                'display_name' => $this->currentTeam->display_name,
            ]),
            'current_project' => $this->whenLoaded('currentProject', fn () => [
                'id' => $this->currentProject->id,
                'name' => $this->currentProject->name,
                'status' => $this->currentProject->status,
            ]),
            'teams' => $this->whenLoaded('teams', fn () => $this->teams->map(fn ($team) => [
                'id' => $team->id,
                'name' => $team->name,
                'display_name' => $team->display_name,
            ])),
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
            ])),
            'counts' => [
                'tasks' => $this->whenCounted('tasks'),
                'teams' => $this->whenCounted('teams'),
                'projects' => $this->whenCounted('projects'),
            ],
            'files' => $this->when(
                $this->relationLoaded('media'),
                fn () => ProfileFileResource::collection(
                    $this->getMedia(User::MEDIA_COLLECTION_FILES)
                )
            ),
            'email_verified_at' => $this->email_verified_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
