<?php

namespace App\Domain\Dialog\Repositories;

use App\Domain\Redis\RedisClient;

class RedisDialogRepository implements DialogRepositoryInterface
{
    private \Redis $redis;

    public function __construct()
    {
        $redisClient = new RedisClient();
        $this->redis = $redisClient->client;
    }

    /**
     * Получить сообщения диалога
     *
     * @param int $dialogId
     * @return array
     */
    public function getDialogMessages(int $dialogId): array
    {
        $key = "dialog:messages:{$dialogId}";
        // Получаем все сообщения из отсортированного множества (от новых к старым)
        // ZREVRANGE возвращает от наибольшего score к наименьшему
        $messages = $this->redis->zRevRange($key, 0, -1, true);

        $result = [];
        foreach ($messages as $messageJson => $score) {
            $data = json_decode($messageJson, true);
            if ($data) {
                $data['timestamp'] = $score;
                $result[] = $data;
            }
        }

        return $result;
    }

    /**
     * Создать сообщение
     *
     * @param array $data
     * @return int|null ID созданного сообщения
     */
    public function createMessage(array $data): ?int
    {
        $messageId = $this->redis->incr('message:next_id');
        $dialogId = $data['dialog_id'];
        $authorUserId = $data['author_user_id'];
        $content = $data['content'];
        $timestamp = microtime(true) * 1000; // миллисекунды

        $messageData = [
            'id' => $messageId,
            'dialog_id' => $dialogId,
            'author_user_id' => $authorUserId,
            'content' => $content,
            'created_at' => $timestamp,
        ];

        $key = "dialog:messages:{$dialogId}";
        $this->redis->zAdd($key, $timestamp, json_encode($messageData));

        // Обновляем время последнего сообщения в диалоге (опционально)
        $this->redis->hSet("dialog:{$dialogId}", 'last_message_at', $timestamp);

        return $messageId;
    }

    /**
     * Получить данные диалога между двумя пользователями
     *
     * @param int $authorUserId
     * @param int $recipientUserId
     * @return array
     */
    public function getDialogData(int $authorUserId, int $recipientUserId): array
    {
        $pairKey = $this->getPairKey($authorUserId, $recipientUserId);
        $dialogId = $this->redis->get($pairKey);

        if (!$dialogId) {
            return [];
        }

        return $this->getDialogById((int) $dialogId);
    }

    /**
     * Создать диалог
     *
     * @param array $data
     * @return int|null ID созданного диалога
     */
    public function createDialog(array $data): ?int
    {
        $dialogId = $this->redis->incr('dialog:next_id');
        $createrUserId = $data['creater_user_id'];
        $recipientUserId = $data['recipientUserId'];
        $name = $data['name'] ?? null;
        $timestamp = time();

        $dialogData = [
            'id' => $dialogId,
            'name' => $name,
            'creater_user_id' => $createrUserId,
            'created_at' => $timestamp,
        ];

        $this->redis->hMSet("dialog:{$dialogId}", $dialogData);

        // Добавляем диалог в индексы пользователей
        $this->redis->sAdd("user:dialogs:{$createrUserId}", $dialogId);
        $this->redis->sAdd("user:dialogs:{$recipientUserId}", $dialogId);

        // Создаем пару пользователей для быстрого поиска
        $pairKey = $this->getPairKey($createrUserId, $recipientUserId);
        $this->redis->set($pairKey, $dialogId);

        return $dialogId;
    }

    /**
     * Получить диалог по ID
     *
     * @param int $dialogId
     * @return array
     */
    private function getDialogById(int $dialogId): array
    {
        $data = $this->redis->hGetAll("dialog:{$dialogId}");
        if (empty($data)) {
            return [];
        }

        // Приводим типы
        $data['id'] = (int) $data['id'];
        $data['creater_user_id'] = (int) $data['creater_user_id'];
        $data['created_at'] = (int) $data['created_at'];

        return $data;
    }

    /**
     * Генерирует ключ для пары пользователей (упорядоченный)
     *
     * @param int $userId1
     * @param int $userId2
     * @return string
     */
    private function getPairKey(int $userId1, int $userId2): string
    {
        if ($userId1 > $userId2) {
            [$userId1, $userId2] = [$userId2, $userId1];
        }
        return "dialog:pair:{$userId1}:{$userId2}";
    }
}