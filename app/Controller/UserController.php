<?php

namespace App\Controller;

use App\Domain\User\Dto\CreateUserDto;
use App\Domain\User\Usecases\CreateUser;
use App\Requests\User\UserGetRequest;
use App\Requests\User\UserRegisterRequest;
use App\Responses\UserGetResponse;
use App\Responses\UserRegisterResponse;

class UserController extends Controller
{
    public function userRegister(): void
    {
        $data = new UserRegisterRequest()->validation();

        $dto = CreateUserDto::createDto($data);

        $user = CreateUser::createUsecase()->createUser($dto);

        UserRegisterResponse::create($user)->json();
    }

    public function userGet(): void
    {
        $data = new UserGetRequest()->validation();

        UserGetResponse::create($data['user'])->json();
    }
}