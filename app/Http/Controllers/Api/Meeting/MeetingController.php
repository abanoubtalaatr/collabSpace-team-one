<?php

namespace App\Http\Controllers\Api\Meeting;

use App\Actions\Meeting\CreateMeetingAction;
use App\Actions\Meeting\DeleteMeetingAction;
use App\Actions\Meeting\UpdateMeetingAction;
use App\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Meeting\CalendarMeetingRequest;
use App\Http\Requests\Meeting\StoreMeetingRequest;
use App\Http\Requests\Meeting\UpdateMeetingRequest;
use App\Http\Resources\MeetingResource;
use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MeetingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $meetings = $this->scopedQuery($user)
            ->when($request->filled('start_date') && $request->filled('end_date'), function ($query) use ($request) {
                $query->whereBetween('starts_at', [
                    Carbon::parse($request->string('start_date'))->startOfDay(),
                    Carbon::parse($request->string('end_date'))->endOfDay(),
                ]);
            })
            ->when($request->filled('project_id'), fn ($query) => $query->where('project_id', $request->integer('project_id')))
            ->orderBy('starts_at')
            ->with(['creator', 'project', 'users', 'teams'])
            ->paginate($request->integer('per_page', 15));

        return $this->success(
            'Meetings retrieved successfully.',
            ['meetings' => MeetingResource::collection($meetings)],
        );
    }

    public function calendar(CalendarMeetingRequest $request): JsonResponse
    {
        $user = $request->user();
        $start = Carbon::parse($request->validated('start_date'))->startOfDay();
        $end = Carbon::parse($request->validated('end_date'))->endOfDay();

        $meetings = $this->scopedQuery($user)
            ->whereBetween('starts_at', [$start, $end])
            ->orderBy('starts_at')
            ->with(['creator', 'project', 'users', 'teams'])
            ->get();

        $calendar = $meetings
            ->groupBy(fn (Meeting $meeting) => $meeting->starts_at->toDateString())
            ->map(fn ($dayMeetings, $date) => [
                'date' => $date,
                'meetings' => MeetingResource::collection($dayMeetings)->resolve(),
            ])
            ->values();

        return $this->success(
            'Meeting calendar retrieved successfully.',
            [
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'calendar' => $calendar,
            ],
        );
    }

    public function upcoming(Request $request): JsonResponse
    {
        $user = $request->user();
        $days = $request->integer('days', 7);

        $meetings = $this->scopedQuery($user)
            ->where('starts_at', '>=', now())
            ->where('starts_at', '<=', now()->addDays($days))
            ->orderBy('starts_at')
            ->with(['creator', 'project', 'users', 'teams'])
            ->get();

        return $this->success(
            'Upcoming meetings retrieved successfully.',
            ['meetings' => MeetingResource::collection($meetings)],
        );
    }

    public function store(StoreMeetingRequest $request, CreateMeetingAction $action): JsonResponse
    {
        $meeting = $action->execute($request->validated(), $request->user());

        return $this->created(
            'Meeting created and invitations sent successfully.',
            ['meeting' => MeetingResource::make($meeting)],
        );
    }

    public function show(Meeting $meeting): JsonResponse
    {
        $this->authorize('view', $meeting);

        $meeting->load(['creator', 'project', 'users', 'teams']);

        return $this->success(
            'Meeting retrieved successfully.',
            ['meeting' => MeetingResource::make($meeting)],
        );
    }

    public function update(UpdateMeetingRequest $request, Meeting $meeting, UpdateMeetingAction $action): JsonResponse
    {
        $this->authorize('update', $meeting);

        $updatedMeeting = $action->execute($meeting, $request->validated(), $request->user());

        return $this->success(
            'Meeting updated and participants notified successfully.',
            ['meeting' => MeetingResource::make($updatedMeeting)],
        );
    }

    public function destroy(Meeting $meeting, DeleteMeetingAction $action, Request $request): JsonResponse
    {
        $this->authorize('delete', $meeting);

        $action->execute($meeting, $request->user());

        return $this->success('Meeting deleted and participants notified successfully.', []);
    }

    private function scopedQuery(User $user)
    {
        return Meeting::query()
            ->where(function ($query) use ($user) {
                $query->where('created_by', $user->id)
                    ->orWhereHas('users', fn ($q) => $q->where('users.id', $user->id));
            });
    }
}
