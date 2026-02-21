<?php

namespace App\Domain\Post\Usecases;

use App\Domain\Post\Repositories\PostRepository;

class GetUserPosts
{
    protected PostRepository $postRepository;

    public function __construct()
    {
        $this->postRepository = new PostRepository();
    }

    public function getPosts(int $userId): array
    {
        $posts = $this->postRepository->getFriendsPosts($userId);

        return $posts;
    }
}