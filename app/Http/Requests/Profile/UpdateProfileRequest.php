<?php

namespace App\Http\Requests\Profile;

use App\Enums\UserAvailability;
use App\Http\Requests\Concerns\ParsesMethodBody;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    use ParsesMethodBody;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeBodyParameters([
            'name',
            'email',
            'phone',
            'country_code',
            'about',
            'job_title',
            'experience_years',
            'availability_status',
            'current_team_id',
            'current_project_id',
        ]);

        // Support PUT /profile?name=Miro and JSON/form bodies.
        $fromQuery = array_filter($this->query(), fn ($value) => $value !== null && $value !== '');

        if ($fromQuery !== []) {
            $this->merge($fromQuery);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_code' => ['nullable', 'string', 'max:10'],
            'about' => ['nullable', 'string', 'max:2000'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'experience_years' => ['nullable', 'integer', 'min:0', 'max:60'],
            'availability_status' => ['sometimes', Rule::in(UserAvailability::values())],
            'current_team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'current_project_id' => ['nullable', 'integer', 'exists:projects,id'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fields = [
                    'name',
                    'email',
                    'phone',
                    'country_code',
                    'about',
                    'job_title',
                    'experience_years',
                    'availability_status',
                    'current_team_id',
                    'current_project_id',
                ];

                $hasAny = collect($fields)->contains(fn (string $field) => $this->exists($field));

                if (! $hasAny) {
                    $validator->errors()->add(
                        'profile',
                        'At least one profile field is required to update.',
                    );
                }
            },
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileAttributes(): array
    {
        $validated = $this->validated();

        $data = collect($validated)
            ->only([
                'name',
                'email',
                'phone',
                'country_code',
                'about',
                'job_title',
                'availability_status',
                'current_team_id',
                'current_project_id',
            ])
            ->all();

        if (array_key_exists('experience_years', $validated)) {
            $data['exp'] = (int) $validated['experience_years'];
        }

        return $data;
    }
}
