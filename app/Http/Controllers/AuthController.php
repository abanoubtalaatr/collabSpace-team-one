<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Auth\LoginUser;
use App\Actions\Auth\RegisterUser;
use App\Actions\Auth\ResendOtp;
use App\Actions\Auth\ResetPassword;
use App\Actions\Auth\SendOtp;
use App\Actions\Auth\VerifyOtp;
use App\Concerns\ApiResponse;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendOtpRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\VerifyOtpRequest;
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

    public function forgotPassword(ForgotPasswordRequest $request, SendOtp $action): JsonResponse
    {
        $action->handle($request->validated('email'), 'password_reset');

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyOtp(VerifyOtpRequest $request, VerifyOtp $action): JsonResponse
    {
        $validated = $request->validated();

        $result = $action->handle(
            $validated['email'],
            $validated['otp'],
            $validated['purpose'],
        );

        return $this->success('OTP verified', $result);
    }

    public function resendOtp(ResendOtpRequest $request, ResendOtp $action): JsonResponse
    {
        $action->handle($request->validated('email'));

        return response()->json(['message' => __('auth.otp_resent')]);
    }

    /**
     * Flow: forgot-password -> verify-otp -> reset-password
     */
    public function resetPassword(ResetPasswordRequest $request, ResetPassword $action): JsonResponse
    {
        $result = $action->handle($request->validated());

        return $this->success('Password reset successful', $result);
    }
}
