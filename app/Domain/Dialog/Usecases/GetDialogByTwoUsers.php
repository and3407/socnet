<?php

namespace App\Domain\Dialog\Usecases;

use App\Domain\Dialog\Repositories\DialogRepository;

class GetDialogByTwoUsers
{
    protected DialogRepository $dialogRepository;

    public function __construct()
    {
        $this->dialogRepository = new DialogRepository();
    }

    public function getDialog(int $userId_1, int $userId_2): array
    {
        $dialogData = $this->dialogRepository->getDialogData($userId_1, $userId_2);

        return $this->dialogRepository->getDialogMessages($dialogData['id']);
    }
}