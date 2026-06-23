<?php

namespace App\DTOs;

use App\Http\Requests\Project\StoreProjectRequest;
use App\Http\Requests\Project\UpdateProjectRequest;
//use Illuminate\Http\Request;

final class ProjectDTO
{
    public function __construct(
        public readonly string  $name,
        public readonly ?string $description,
        public readonly ?string $startDate,
        public readonly ?string $deadline,
        public readonly string  $priority,
        public readonly string  $status,
        public readonly int     $createdBy,
        //public readonly array   $teamIds = [],
        public readonly array   $mediaFiles = [],
    ) {}

    public static function fromStoreRequest(StoreProjectRequest $request): self
    {
        return new self(
            name:        $request->validated('name'),
            description: $request->validated('description'),
            startDate:   $request->validated('start_date'),
            deadline:    $request->validated('deadline'),
            priority:    $request->validated('priority'),
            status:      $request->validated('status', 'pending'),
            createdBy:   $request->user()->id,
         //   teamIds:     $request->validated('team_ids', []),
            mediaFiles:  $request->file('attachments', []),
        );
    }

    public static function fromUpdateRequest(UpdateProjectRequest $request): self
    {
        return new self(
            name:        $request->validated('name'),
            description: $request->validated('description'),
            startDate:   $request->validated('start_date'),
            deadline:    $request->validated('deadline'),
            priority:    $request->validated('priority'),
            status:      $request->validated('status'),
            createdBy:   $request->user()->id,
          //  teamIds:     $request->validated('team_ids', []),
            mediaFiles:  $request->file('attachments', []),
        );
    }
}