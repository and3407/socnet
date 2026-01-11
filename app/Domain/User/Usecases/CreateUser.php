<?php

namespace App\Domain\User\Usecases;

use App\Domain\User\Dto\CreateUserDto;
use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Utils\Uuid;

class CreateUser
{
    protected UserRepository $userRepository;

    public function __construct() {
        $this->userRepository = new UserRepository();
    }
    public static function createUsecase(): self
    {
        return new self();
    }

    public function createUser(CreateUserDto $dto): User
    {
        $uuid = Uuid::createUuid();

        $userData = [
            'uuid' => $uuid,
            'first_name' => $dto->firstName,
            'second_name' => $dto->secondName,
            'birthdate' => \DateTime::createFromFormat('Y-m-d', $dto->birthdate)->format('Y-m-d'),
            'biography' => $dto->biography,
            'city' => $dto->city,
            'password' => password_hash($dto->password, PASSWORD_DEFAULT),
        ];

        $this->userRepository->createUser($userData);

        return $this->userRepository->getUserByUuid($uuid);
    }
}