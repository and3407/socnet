<?php

namespace App\Requests\User;

use App\Domain\User\Repositories\UserRepository;
use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class UserPostsRequest extends Request
{
    public function validation(): array
    {
        $params = $this->getQueryParams();

        if (empty($_SESSION['authUserId'])) {
            ErrorResponse::createJson('Invalid parameters.', HttpCode::BAD_REQUEST);
        }

        $user = $this->getUserRepository()->getUserById($_SESSION['authUserId']);

        if ($user === null) {
            ErrorResponse::createJson('User not found', HttpCode::NOT_FOUND);
        }

        return [
            'user' => $user,
            'page' => $params['page'] ?? 1,
        ];
    }

    protected function getUserRepository(): UserRepository
    {
        return new UserRepository();
    }
}