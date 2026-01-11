<?php

namespace App\Requests\Auth;

use App\Domain\User\Repositories\UserRepository;
use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class LoginRequest extends Request
{
    public function validation(): array
    {
        $data = $this->getJsonData();

        if (!$data['uuid'] || !$data['password']) {
            ErrorResponse::createJson('Invalid parameters.', HttpCode::BAD_REQUEST);
        }

        $user = $this->getUserRepository()->getUserByUuid($data['uuid']);

        if ($user === null) {
            ErrorResponse::createJson('User not found', HttpCode::NOT_FOUND);
        }

        if (!password_verify($data['password'], $user->password)) {
            ErrorResponse::createJson('Invalid password', HttpCode::UNAUTHORIZED);
        }

        return [
            'user' => $user,
        ];
    }

    private function getUserRepository(): UserRepository
    {
        return new UserRepository();
    }
}