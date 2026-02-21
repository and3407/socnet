<?php

namespace App\Database\migrations\upload_friends;

use App\Domain\User\Post\Repositories\PostRepository;
use App\Domain\User\Repositories\UserRepository;

class UploadFriends
{
    protected UserRepository $userRepository;
    protected PostRepository $postRepository;


    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->postRepository = new PostRepository();
    }

    public function execute(): void
    {
        foreach ($this->userRepository->getAllUsers() as $user) {
            $ids = $this->postRepository->getRandomAuthorUserId();

            echo $user->id . PHP_EOL;

            $this->userRepository->addFriends($user->id, $ids);
        }

        echo 'End' . PHP_EOL;
    }
}