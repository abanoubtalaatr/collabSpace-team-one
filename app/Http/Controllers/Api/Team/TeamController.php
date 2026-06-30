<?php

namespace App\Http\Controllers\Api\Team;

use App\Http\Controllers\Controller;
use App\Http\Requests\Team\StoreTeamRequest;
use App\Http\Requests\Team\UpdateTeamRequest;
use App\Http\Resources\TeamResource;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TeamController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $teams = Team::query()
            ->withCount(['members', 'projects'])
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('name', 'like', '%'.$request->string('search').'%')
            )
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return TeamResource::collection($teams);
    }

    public function store(StoreTeamRequest $request): TeamResource
    {
        $team = Team::create([
            'name' => $request->validated('name'),
            'display_name' => $request->validated('display_name') ?? $request->validated('name'),
            'description' => $request->validated('description'),
        ]);

        return new TeamResource($team);
    }

    public function show(Team $team): TeamResource
    {
        $team->load(['members:id,name,email', 'projects:id,name'])
            ->loadCount(['members', 'projects']);

        return new TeamResource($team);
    }

    public function update(UpdateTeamRequest $request, Team $team): TeamResource
    {
        $team->update($request->validated());
        $team->load(['members:id,name,email', 'projects:id,name'])
            ->loadCount(['members', 'projects']);

        return new TeamResource($team);
    }

    public function destroy(Team $team): JsonResponse
    {
        $team->delete();

        return response()->json(['message' => 'Team deleted successfully.']);
    }
}
