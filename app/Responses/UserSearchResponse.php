<?php

namespace App\Responses;

use App\Domain\User\Models\User;

class UserSearchResponse extends Response
{
    /**
     * @param User[] $users
     */
    public function __construct(
        public readonly array $users
    ) { }

    /**
     * @param User[] $users
     */
    public static function create(array $users): self
    {
        return new self($users);
    }

    /**
     * @throws \JsonException
     */
    public function json(): void
    {
        $result = [];

        foreach ($this->users as $user) {
            $result[] = [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'first_name' => $user->firstName,
                'second_name' => $user->secondName,
                'birthdate' => $user->birthdate,
                'biography' => $user->biography,
                'city' => $user->city,
            ];
        }


        $this->successResponse($result);
    }
}