<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(RegisterRequest $request, AuthService $service)
    {
        $data = $service->register($request->all());

        return ApiResponse::created($data, 'User registered successfully');
    }

    public function login(LoginRequest $request, AuthService $service)
    {
        $credentials = $request->only(['email', 'password']);

        if (! Auth::attempt($credentials)) {
            return ApiResponse::unauthorized(trans('auth.failed'));
        }

        $data = $service->login($credentials);

        return ApiResponse::success($data, 'Logged in successfully');
    }
}
