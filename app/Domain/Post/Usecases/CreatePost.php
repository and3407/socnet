<?php

namespace App\Domain\Post\Usecases;

use App\Domain\Post\Dto\CreatePostDto;
use App\Domain\Post\Repositories\PostRepository;
use App\Domain\RabbitMQ\RabbitMQClient;

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

        // Publish to RabbitMQ queue
        try {
            $message = json_encode([
                'post_id' => $postId,
                'author_user_id' => $dto->authorUserId,
                'created_at' => $createdAt,
                'text_preview' => substr($dto->text, 0, 100),
            ], JSON_THROW_ON_ERROR);
            RabbitMQClient::publish('post_created', $message);
        } catch (\Exception $e) {
            // Log error (optional) but do not fail the request
            error_log('Failed to publish to RabbitMQ: ' . $e->getMessage());
        }

        return $postId;
    }
}