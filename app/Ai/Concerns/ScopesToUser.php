<?php

namespace App\Ai\Concerns;

use App\Models\User;

trait ScopesToUser
{
    public function __construct(protected readonly User $user) {}
}
