<?php

namespace App\Responses;

use App\Domain\User\Models\User;

class UserGetResponse extends Response
{
    public function __construct(
        private readonly User $user,
        private readonly array $data,
    ) { }

    public static function create(User $user, array $data): self
    {
        return new self($user, $data);
    }

    public function json(): void
    {
        $user = [
            'id' => $this->user->id,
            'uuid' => $this->user->uuid,
            'first_name' => $this->user->firstName,
            'second_name' => $this->user->secondName,
            'birthdate' => $this->user->birthdate,
            'biography' => $this->user->biography,
            'city' => $this->user->city,
        ];

        $stat = $this->data['stat'];

        $result = [
            'user' => $user,
            'stat' => $stat,
        ];

        $this->successResponse($result);
    }
}