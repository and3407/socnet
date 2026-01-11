<?php

namespace App\Responses;

use App\Domain\Token\Models\Token;

class LoginResponse extends Response
{
    public function __construct(
        private readonly Token $token,
    ) { }

    public static function create(Token $token): self
    {
        return new self($token);
    }

    public function json(): void
    {
        $result = [
            'token' => $this->token->token,
        ];

        $this->successResponse($result);
    }
}