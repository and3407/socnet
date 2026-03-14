<?php

namespace App\Responses;

class PostCreateResponse extends Response
{
    public function __construct(
        private readonly int $postId,
    ) { }

    public static function create(int $postId): self
    {
        return new self($postId);
    }

    public function json(): void
    {
        $result = [
            'post_id' => $this->postId,
        ];

        $this->successResponse($result);
    }
}