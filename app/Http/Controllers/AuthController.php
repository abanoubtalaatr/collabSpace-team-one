<?php

namespace App\Http\Controllers;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\RegisterUser;
use App\Concerns\ApiResponse;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request, RegisterUser $action): JsonResponse
    {
        $user = $action->handle($request->validated());

        return $this->created('Registration successful. Please verify your email.', [
            'user' => $user,
        ]);
    }

    public function login(LoginRequest $request, LoginUser $action): JsonResponse
    {
        $result = $action->handle($request->validated());

        return $this->success('Logged in successfully', $result);
    }

    public function logout(): JsonResponse
    {
        Auth::user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'You have successfully been logged out',
        ], Response::HTTP_OK);
    }
}
