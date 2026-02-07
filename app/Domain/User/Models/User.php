<?php

namespace App\Domain\User\Models;

class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $uuid,
        public readonly string $firstName,
        public readonly string $secondName,
        public readonly string $birthdate,
        public readonly ?string $biography,
        public readonly string $city,
        public readonly string $password,
    ) { }
}