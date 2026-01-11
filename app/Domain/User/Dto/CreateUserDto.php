<?php

namespace App\Domain\User\Dto;

class CreateUserDto
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $secondName,
        public readonly string $birthdate,
        public readonly string $biography,
        public readonly string $city,
        public readonly string $password,
    ) { }

    public static function createDto(array $data): self
    {
        return new self(
            $data['first_name'],
            $data['second_name'],
            $data['birthdate'],
            $data['biography'],
            $data['city'],
            $data['password'],
        );
    }
}