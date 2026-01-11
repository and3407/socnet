<?php

namespace App\Responses;

use App\Domain\User\Models\User;

class UserRegisterResponse extends Response
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
            'user_id' => $this->user->uuid,
        ];

        $this->successResponse($result);
    }
}