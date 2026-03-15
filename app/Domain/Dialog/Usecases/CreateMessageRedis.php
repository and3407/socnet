<?php

namespace App\Domain\Dialog\Usecases;

use App\Domain\Dialog\Repositories\RedisDialogRepository;

class CreateMessageRedis
{
    protected RedisDialogRepository $dialogRepository;

    public function __construct()
    {
        $this->dialogRepository = new RedisDialogRepository();
    }

    public function createMessage(int $authorUserId, array $data): array
    {
        $recipientUserId = $data['recipientUserId'];
        $content = $data['content'];

        $dialog = $this->dialogRepository->getDialogData($authorUserId, $recipientUserId);

        $dialogId = !empty($dialog['id']) ? $dialog['id'] : $this->dialogRepository->createDialog([
            'creater_user_id' => $authorUserId,
            'recipientUserId' => $recipientUserId,
        ]);

        $messageId = $this->dialogRepository->createMessage([
            'dialog_id' => $dialogId,
            'author_user_id' => $authorUserId,
            'content' => $content,
        ]);

        return [
            'messageId' => $messageId,
            'dialogId' => $dialogId,
        ];
    }
}