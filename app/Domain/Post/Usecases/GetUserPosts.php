<?php

namespace App\Domain\Post\Usecases;

use App\Domain\Post\Repositories\PostCacheRepository;
use App\Domain\Post\Repositories\PostRepository;
use App\Domain\Redis\RedisClient;

class GetUserPosts
{
    protected const string CACHE_KEY_PATTERN = 'posts:%d:posts';
    protected const int MAX_POSTS = 1000;
    protected const int CACHE_TTL = 300;
    protected const int DEFAULT_PAGE_SIZE = 20;

    protected PostRepository $postRepository;
    protected RedisClient $redis;

    public function __construct()
    {
        $this->postRepository = new PostRepository();
        $this->redis = new RedisClient();
    }

    public function getPosts(int $userId): array
    {
        // Пытаемся получить данные из кеша
        $cachedPosts = $this->getCache($userId);

        if ($cachedPosts !== null) {
            return $cachedPosts;
        }

        // Если кеша нет, создаем его и возвращаем данные
        $this->createCache($userId);

        return $this->getCache($userId) ?? [];
    }

    /**
     * Получение постов с пагинацией
     */
    public function getCacheWithPagination(int $userId, int $page = 1, int $limit = self::DEFAULT_PAGE_SIZE): array
    {
        $key = $this->getUserPostsKey($userId);

        if (!$this->hasCache($userId)) {
            $this->createCache($userId);
        }

        // Валидация входных параметров
        $page = max(1, $page);
        $limit = max(1, min(100, $limit)); // Ограничиваем максимальный размер страницы

        // Получаем общее количество постов в кеше
        $totalItems = $this->redis->client->zCard($key);

        // Рассчитываем общее количество страниц
        $totalPages = (int) ceil($totalItems / $limit);

        // Корректируем номер страницы, если он превышает общее количество
        if ($page > $totalPages && $totalPages > 0) {
            $page = $totalPages;
        }

        // Рассчитываем смещение
        $offset = ($page - 1) * $limit;

        // Если нет данных или страница невалидна, возвращаем пустой результат
        if ($totalItems === 0 || $offset >= $totalItems) {
            return [
                'data' => [],
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $totalItems,
                'per_page' => $limit,
                'has_previous' => false,
                'has_next' => false,
            ];
        }

        // Получаем срез данных с пагинацией (zRevRange для сортировки от новых к старым)
        $cachedData = $this->redis->client->zRevRange($key, $offset, $offset + $limit - 1);

        $posts = [];
        foreach ($cachedData as $serializedPost) {
            try {
                $posts[] = json_decode($serializedPost, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                continue;
            }
        }

        return [
            'data' => $posts,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'per_page' => $limit,
            'has_previous' => $page > 1,
            'has_next' => $page < $totalPages,
        ];
    }

    public function hasCache(int $userId): bool
    {
        $key = $this->getUserPostsKey($userId);
        return $this->redis->client->exists($key) > 0;
    }

    public function refreshCache(int $userId): bool
    {
        $this->deleteCache($userId);
        $this->createCache($userId);

        return $this->hasCache($userId);
    }

    public function deleteCache(int $userId): bool
    {
        $key = $this->getUserPostsKey($userId);
        return $this->redis->client->del($key) > 0;
    }

    public function getCache(int $userId): ?array
    {
        $key = $this->getUserPostsKey($userId);

        // Получаем все элементы из сортированного множества с сортировкой по времени (от новых к старым)
        $cachedData = $this->redis->client->zRevRange($key, 0, -1);

        if (empty($cachedData)) {
            return null;
        }

        // Декодируем JSON данные
        $posts = [];
        foreach ($cachedData as $serializedPost) {
            try {
                $posts[] = json_decode($serializedPost, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                // Логируем ошибку и пропускаем поврежденный пост
                error_log("Failed to decode cached post for user {$userId}: " . $e->getMessage());
                continue;
            }
        }

        return $posts;
    }

    public function createCache(int $userId): void
    {
        $posts = $this->postRepository->getFriendsPosts($userId);

        $key = $this->getUserPostsKey($userId);

        // Очищаем старые данные
        $this->redis->client->del($key);

        $postsToCache = array_slice($posts, 0, self::MAX_POSTS);

        // Для phpredis используем multi() или добавляем по одному
        $this->redis->client->multi();

        foreach ($postsToCache as $postData) {
            $serializedPost = json_encode($postData, JSON_THROW_ON_ERROR);
            $timestamp = strtotime($postData['created_at']);

            // В phpredis zAdd принимает: key, score, value
            $this->redis->client->zAdd($key, $timestamp, $serializedPost);
        }

        $this->redis->client->expire($key, self::CACHE_TTL);
        $this->redis->client->exec();

    }

    protected function getUserPostsKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY_PATTERN, $userId);
    }
}