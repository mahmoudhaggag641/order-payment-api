<?php

namespace App\Repositories;

use App\Helpers\ApiResponse;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class UserRepository extends BaseRepository
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail($email)
    {
        $user = $this->model->where('email', $email)->first();

        if (!$user) {
            throw new HttpResponseException(ApiResponse::notFound(trans('auth.failed')));
        }

        return $user;
    }

    public function query($query, array $params = []) {}

    public function transform($paginator)
    {
        return $paginator->through(fn($user) => new UserResource($user));
    }

    public function formatParams($params, $user = null): array
    {
        $formatted = [
            'name' => gv($params, 'name'),
            'email' => gv($params, 'email'),
        ];

        if (! $user) {
            $formatted['password'] = Hash::make(gv($params, 'password'));
        }

        return $formatted;
    }

    public function setRelations($user, array $params)
    {
        $this->assignRole($user);
    }

    public function canDelete($user) {}

    private function assignRole(User $user) {}
}
