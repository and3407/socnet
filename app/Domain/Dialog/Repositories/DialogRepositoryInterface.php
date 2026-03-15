<?php

namespace App\Domain\Dialog\Repositories;

interface DialogRepositoryInterface
{
    /**
     * Получить сообщения диалога
     *
     * @param int $dialogId
     * @return array
     */
    public function getDialogMessages(int $dialogId): array;

    /**
     * Создать сообщение
     *
     * @param array $data
     * @return int|null ID созданного сообщения
     */
    public function createMessage(array $data): ?int;

    /**
     * Получить данные диалога между двумя пользователями
     *
     * @param int $authorUserId
     * @param int $recipientUserId
     * @return array
     */
    public function getDialogData(int $authorUserId, int $recipientUserId): array;

    /**
     * Создать диалог
     *
     * @param array $data
     * @return int|null ID созданного диалога
     */
    public function createDialog(array $data): ?int;
}