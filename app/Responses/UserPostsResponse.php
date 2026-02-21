<?php

namespace App\Responses;

use App\Domain\User\Models\User;

class UserPostsResponse extends Response
{
    public User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public static function create(User $user): self
    {
        return new self($user);
    }

    public function json(array $posts): void
    {
        $result = $posts;


        $this->successResponse($result);
    }
}