<?php

namespace App\Database\migrations\upload_posts;

use App\Domain\User\Post\Repositories\PostRepository;
use App\Domain\User\Repositories\UserRepository;
use Random\RandomException;

class PostCreator
{
    protected UserRepository $userRepository;
    protected PostRepository $postRepository;

    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->postRepository = new PostRepository();
    }

    /**
     * @throws RandomException
     */
    public function execute(): void
    {
        $posts = $this->getPosts();

        $totalPosts = count($posts);

        echo 'Найдено постов: ' . $totalPosts . PHP_EOL;

        $firstUser = $this->userRepository->getFirstUser();

        $userPosts = [];
        $userId = $firstUser?->id;

        foreach ($posts as $num => $post) {
            $userPosts[] = [
                'author_user_id' => $userId,
                'text' => $post,
                'created_at' => $this->getRandomDate(),
            ];

            if (count($userPosts) === 15) {
                $user = $this->userRepository->getUserById($userId);

                echo $num . '. ' .$user?->id . ' - ' . count($userPosts) . PHP_EOL;

                $this->savePosts($userPosts);

                $userId++;
                $userPosts = [];
            }
        }
    }

    /**
     * @throws RandomException
     */
    protected function getRandomDate($startDate = '2020-01-01', $endDate = '2024-12-31'): string
    {
        $start = strtotime($startDate);
        $end = strtotime($endDate);

        $randomTimestamp = random_int($start, $end);

        return date('Y-m-d H:i:s', $randomTimestamp);
    }

    protected function savePosts(array $posts): void
    {
        foreach ($posts as $post) {
            $this->postRepository->createPost($post);
        }
    }

    protected function getPosts(): array
    {
        // Чтение файла
        $content = file_get_contents('posts.txt');

        // Разделение по строкам
        $lines = explode("\n", $content);

        // Обработка каждой строки как отдельного поста
        $posts = array_map('trim', $lines);

        // Удаление пустых строк
        $posts = array_filter($posts, function($line) {
            return !empty($line);
        });

        // Переиндексация массива
        return array_values($posts);
    }
}