<?php

namespace App\Domain\User\Usecases;

use App\Domain\User\Models\User;
use App\Domain\User\Repositories\UserRepository;
use App\Requests\User\UserGetRequest;

class SearchUsers
{
    protected UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * @param string $firstName
     * @param string $lastName
     * @return User[]
     */
    public function searchByName(string $firstName, string $lastName): array
    {
        return $this->userRepository->searchByName($firstName, $lastName);
    }
}