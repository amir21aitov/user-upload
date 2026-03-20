<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Contracts\AuthServiceInterface;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    public function register(RegistrationRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->toDTO());

        return response()->json([
            'user_id' => $result->userId,
            'resend_in' => $result->resendIn,
        ], Response::HTTP_CREATED);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->authService->verifyOtp(
            $request->validated('email'),
            $request->validated('code'),
        );

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->toDTO());

        return response()->json([
            'data' => new UserResource($result->user),
            'token' => $result->token,
        ]);
    }
}
