<?php

namespace App\Requests;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

abstract class Request
{
    public function validation(): array
    {
        return [];
    }

    public function getJsonData(): array
    {
        $jsonData = file_get_contents('php://input');

        return json_decode($jsonData, true);
    }

    public function getQueryParams(): array
    {
        return $_GET;
    }

    public function getAuthUser(): User
    {
        if (empty($_SESSION['authUserId'])) {
            ErrorResponse::createJson('Invalid parameters.', HttpCode::BAD_REQUEST);
        }

        return $this->getUserRepository()->getUserById($_SESSION['authUserId']);
    }

    protected function getUserRepository(): UserRepository
    {
        return new UserRepository();
    }
}