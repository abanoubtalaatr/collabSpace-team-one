<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use UnitEnum;

class TeamMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'job_title' => $this->optionalAttribute('job_title'),
            'phone' => $this->optionalAttribute('phone'),
            'country_code' => $this->optionalAttribute('country_code'),
            'experience_years' => $this->optionalAttribute('exp'),
            'availability_status' => $this->optionalEnumValue('availability_status'),
            'avatar_url' => $this->avatarUrl(),
        ];
    }

    private function optionalAttribute(string $key): mixed
    {
        return $this->offsetExists($key) ? $this->getAttribute($key) : null;
    }

    private function optionalEnumValue(string $key): mixed
    {
        if (! $this->offsetExists($key)) {
            return null;
        }

        $value = $this->getAttribute($key);

        return $value instanceof UnitEnum ? $value->value : $value;
    }
}
