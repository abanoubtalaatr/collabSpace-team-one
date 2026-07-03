<?php

namespace App\Http\Controllers\Api\Project;

use App\Actions\Project\GetProjectGuestsAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\TeamMemberResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProjectGuestController extends Controller
{
    public function __construct(
        private readonly GetProjectGuestsAction $action,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return TeamMemberResource::collection(
            $this->action->execute()
        );
    }
}