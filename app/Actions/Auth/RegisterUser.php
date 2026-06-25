<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;

class RegisterUser
{
    public function __construct(private SendOtp $sendOtp) {}

    public function handle(array $data): User
    {
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        $this->sendOtp->handle($user->email, 'registration', $user->name);

        return $user;
    }
}
