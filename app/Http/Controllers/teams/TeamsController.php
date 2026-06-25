<?php

namespace App\Http\Controllers\teams;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Team;

class TeamsController extends Controller
{
    public function index()
    {
        $teams = Team::all();
        return response()->json($teams);
    }

    public function create()
    {
        return response()->json(['message' => 'Display form to create a new team']);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);
        $team = Team::create($request->all());
        return response()->json($team, 201);
    }

    public function show($teamId)
    {
        $team = Team::with(['members', 'projects'])->findOrFail($teamId);
        return response()->json($team);
    }

    public function edit($teamId)
    {
        $team = Team::with(['members', 'projects'])->findOrFail($teamId);
        return response()->json(['message' => 'Display form to edit the team', 'team' => $team]);
    }

    public function update(Request $request, $teamId)
    {
        $team = Team::with(['members'])->findOrFail($teamId);
        $team->update($request->all());
        return response()->json($team);
    }

    public function delete($teamId)
    {
        $team = Team::with(['members',])->findOrFail($teamId);
        $team->delete();
        return response()->json(null, 204);
    }
}
