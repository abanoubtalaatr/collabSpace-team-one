<?php

namespace App\Actions\Auth;

use App\Models\User;

class RegisterUser
{
    public function handle(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);

        return $user;
    }
}
