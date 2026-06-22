<?php

namespace App\Http\Resources;

use App\Models\Project;
use App\Models\Role;
use App\Models\Task;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SearchResultResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if (is_array($this->resource)) {
            return $this->resource;
        }

        return [
            'type' => $this->resource->type,
            'id' => $this->resource->searchable->getKey(),
            'title' => $this->resource->title,
            'data' => $this->searchableData($this->resource->searchable),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function searchableData(Model $searchable): array
    {
        match (true) {
            $searchable instanceof User => $searchable->loadMissing([
                'projects:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
                'teams:id,name,display_name,description,created_at,updated_at',
                'tasks:id,project_id,name,description,created_at,updated_at',
                'roles:id,name,display_name,description,created_at,updated_at',
            ]),
            $searchable instanceof Project => $searchable->loadMissing([
                'creator:id,name,email,email_verified_at,created_at,updated_at',
                'teams:id,name,display_name,description,created_at,updated_at',
                'tasks:id,project_id,name,description,created_at,updated_at',
            ]),
            $searchable instanceof Task => $searchable->loadMissing([
                'project:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
                'users:id,name,email,email_verified_at,created_at,updated_at',
            ]),
            $searchable instanceof Team => $searchable->loadMissing([
                'members:id,name,email,email_verified_at,created_at,updated_at',
                'projects:id,creatd_by,name,description,start_date,deadline,priority,status,created_at,updated_at',
            ]),
            $searchable instanceof Role => $searchable->loadMissing([
                'users:id,name,email,email_verified_at,created_at,updated_at',
            ]),
            default => null,
        };

        return $searchable->toArray();
    }
}
