<?php

namespace App\Http\Controllers\Api;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Enums\HttpCode;
use App\Helpers\ResponseHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegistrationRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ){}

    public function register(RegistrationRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromRequest($request->validated());
        $result = $this->authService->setOtpCode($dto);

        return ResponseHelper::response($result, HttpCode::CREATED);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->authService->verifyOtpCode(
            $request->validated('email'),
            $request->validated('code'),
        );

        return ResponseHelper::response(new UserResource($user));
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request->validated());
        $result = $this->authService->login($dto);

        return ResponseHelper::response([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }
}
