<?php

namespace App\Filters;
use App\Filters\QueryFilter;

class ProjectFilter extends QueryFilter
{
    public function status($value): void
    {
        $this->builder->where('status', $value);
    }

    public function priority($value): void
    {
        $this->builder->where('priority', $value);
    }

    public function created_by($value): void
    {
        $this->builder->where('created_by', $value);
    }

    public function start_date($value): void
    {
        $this->builder->whereDate('start_date', '>=', $value);
    }

    public function deadline($value): void
    {
        $this->builder->whereDate('deadline', '<=', $value);
    }

    public function search($value): void
    {
        $this->builder->where('name', 'like', "%{$value}%");
    }
}