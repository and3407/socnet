<?php

namespace App\Domain\Post\Usecases;

use App\Domain\Post\Dto\CreatePostDto;
use App\Domain\Post\Repositories\PostRepository;

class CreatePost
{
    protected PostRepository $postRepository;

    public function __construct() {
        $this->postRepository = new PostRepository();
    }

    public static function createUsecase(): self
    {
        return new self();
    }

    public function createPost(CreatePostDto $dto): int
    {
        $createdAt = date('Y-m-d H:i:s');

        $postData = [
            'text' => $dto->text,
            'author_user_id' => $dto->authorUserId,
            'created_at' => $createdAt,
        ];

        $postId = $this->postRepository->createPost($postData);

        return $postId;
    }
}