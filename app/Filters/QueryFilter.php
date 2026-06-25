<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

abstract class QueryFilter
{
    protected $request;

    protected $builder;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;
        foreach ($this->filterableParameters() as $name => $value) {
            $this->{$name}($value);
        }

        return $this->builder;
    }

    protected function filterableParameters(): array
    {
        return collect($this->request->keys())
            ->filter(fn (string $key) => method_exists($this, $key))
            ->mapWithKeys(fn (string $key) => [$key => $this->request->input($key)])
            ->all();
    }
}
