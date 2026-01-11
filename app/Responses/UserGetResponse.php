<?php

namespace App\Responses;

use App\Domain\User\Models\User;

class UserGetResponse extends Response
{
    public function __construct(
        private readonly User $user,
    ) { }

    public static function create(User $user): self
    {
        return new self($user);
    }

    public function json(): void
    {
        $result = [
            'id' => $this->user->id,
            'uuid' => $this->user->uuid,
            'first_name' => $this->user->firstName,
            'second_name' => $this->user->secondName,
            'birthdate' => $this->user->birthdate,
            'biography' => $this->user->biography,
            'city' => $this->user->city,
        ];

        $this->successResponse($result);
    }
}