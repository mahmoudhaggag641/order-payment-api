<?php

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Repositories\UserRepository;

class AuthService
{
    public function __construct(public UserRepository $repo) {}

    public function register($params)
    {
        $user = $this->repo->create($params);

        $this->sendVerificationEmail($user);

        return $this->respondWithToken($user);
    }

    public function login($params)
    {
        $user = $this->repo->findByEmail(gv($params, 'email'));

        return $this->respondWithToken($user);
    }

    private function respondWithToken($user)
    {
        $tokenName = gv(request()->all(), 'device_name', 'auth_token');
        $token = $user->createToken($tokenName)->plainTextToken;

        return [
            'access_token' => $token,
            'token_type'   => 'bearer',
            'user'         => new UserResource($user),
        ];
    }

    private function sendVerificationEmail($user) {}
}
