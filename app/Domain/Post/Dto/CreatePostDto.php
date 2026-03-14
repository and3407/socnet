<?php

namespace App\Domain\Post\Dto;

class CreatePostDto
{
    public function __construct(
        public readonly string $text,
        public readonly int $authorUserId,
    ) { }

    public static function createDto(array $data): self
    {
        return new self(
            $data['text'],
            $data['user']->id,
        );
    }
}