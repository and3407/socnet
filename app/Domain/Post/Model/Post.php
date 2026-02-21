<?php

namespace App\Domain\Post\Model;

class Post
{
    public function __construct(
        public readonly int $id,
        public readonly string $text,
        public readonly int $authorUserId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly string $deletedAt,
    ) {}
}