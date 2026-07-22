<?php

namespace App\Http\Requests\Meeting;

use App\Models\Meeting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Validator;

class UpdateMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['sometimes', 'date', 'after:now'],
            'ends_at' => ['sometimes', 'date'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['starts_at', 'ends_at'])) {
                    return;
                }

                $meeting = $this->route('meeting');

                if (! $meeting instanceof Meeting) {
                    return;
                }

                $startsAt = $this->filled('starts_at')
                    ? Carbon::parse($this->input('starts_at'))
                    : $meeting->starts_at;
                $endsAt = $this->filled('ends_at')
                    ? Carbon::parse($this->input('ends_at'))
                    : $meeting->ends_at;

                if ($this->filled('starts_at') && Carbon::parse($this->input('starts_at'))->lt(now())) {
                    $validator->errors()->add(
                        'starts_at',
                        'The start and end dates have to be now or in the future.',
                    );
                }

                if ($endsAt->lte($startsAt)) {
                    $validator->errors()->add(
                        'ends_at',
                        'The end date must be after the start date.',
                    );
                }
            },
        ];
    }
}
