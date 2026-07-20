<?php

namespace App\Http\Requests\Meeting;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'location' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
            'team_ids' => ['nullable', 'array'],
            'team_ids.*' => ['integer', 'exists:teams,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'starts_at.after' => 'The meeting start time must be in the future.',
            'ends_at.after' => 'The meeting end time must be after the start time.',
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $hasProject = $this->filled('project_id');
                $userIds = $this->input('user_ids', []);
                $teamIds = $this->input('team_ids', []);
                $hasUsers = is_array($userIds) && $userIds !== [];
                $hasTeams = is_array($teamIds) && $teamIds !== [];

                if (! $hasProject && ! $hasUsers && ! $hasTeams) {
                    $validator->errors()->add(
                        'participants',
                        'A meeting must have a project, user, or team.',
                    );
                }
            },
        ];
    }
}
