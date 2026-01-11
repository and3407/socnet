<?php

namespace App\Controller;

use App\Domain\Token\Usecases\CreateToken;
use App\Domain\User\Models\User;
use App\Requests\Auth\LoginRequest;
use App\Responses\LoginResponse;

class AuthController extends Controller
{
    public function login(): void
    {
        $data = new LoginRequest()->validation();

        $user = $data['user'];
        assert($user instanceof User);

        $token = CreateToken::createUsecase()->createToken($user->id);

        LoginResponse::create($token)->json();
    }
}