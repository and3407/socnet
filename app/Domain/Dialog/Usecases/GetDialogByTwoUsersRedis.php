<?php

namespace App\Domain\Dialog\Usecases;

use App\Domain\Dialog\Repositories\RedisDialogRepository;

class GetDialogByTwoUsersRedis
{
    protected RedisDialogRepository $dialogRepository;

    public function __construct()
    {
        $this->dialogRepository = new RedisDialogRepository();
    }

    public function getDialog(int $userId_1, int $userId_2): array
    {
        $dialogData = $this->dialogRepository->getDialogData($userId_1, $userId_2);

        if (empty($dialogData)) {
            return [];
        }

        return $this->dialogRepository->getDialogMessages($dialogData['id']);
    }
}