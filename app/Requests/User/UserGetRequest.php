<?php

namespace App\Requests\User;

use App\Domain\User\Repositories\UserRepository;
use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class UserGetRequest extends Request
{
    public function validation(): array
    {
        $data = $this->getQueryParams();

        if (!$data['uuid']) {
            ErrorResponse::createJson('Invalid parameters.', HttpCode::BAD_REQUEST);
        }

        $user = $this->getUserRepository()->getUserByUuid($data['uuid']);

        if ($user === null) {
            ErrorResponse::createJson('User not found', HttpCode::NOT_FOUND);
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