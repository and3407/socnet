<?php

namespace App\Domain\Token\Models;

class Token
{
    public function __construct(
        public readonly int $id,
        public readonly int $userid,
        public readonly string $token,
        public readonly string $createdAt,
        public readonly string $expiredAt,
    ) {}
}