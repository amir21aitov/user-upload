<?php

namespace App\Http\Middleware;

use App\Enums\HttpCode;
use App\Helpers\ResponseHelper;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ValidateJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();

            if (!$user) {
                return ResponseHelper::response([
                        'message' => 'User not found.'
                    ], HttpCode::UNAUTHORIZED,
                );
            }
        } catch (TokenExpiredException) {
            return ResponseHelper::response([
                    'message' => 'Token has expired.'
                ], HttpCode::UNAUTHORIZED,
            );
        } catch (TokenInvalidException) {
            return ResponseHelper::response([
                    'message' => 'Token is invalid.'
                ], HttpCode::UNAUTHORIZED,
            );
        } catch (JWTException) {
            return ResponseHelper::response([
                    'message' => 'Token not provided.'
                ], HttpCode::UNAUTHORIZED,
            );
        }

        return $next($request);
    }
}
