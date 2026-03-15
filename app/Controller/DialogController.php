<?php

namespace App\Controller;

use App\Domain\Dialog\Usecases\CreateMessage;
use App\Domain\Dialog\Usecases\GetDialogByTwoUsers;
use App\Domain\Dialog\Usecases\CreateMessageRedis;
use App\Domain\Dialog\Usecases\GetDialogByTwoUsersRedis;
use App\Requests\Dialog\GetDialogByUserRequest;
use App\Requests\Dialog\SendMessageRequest;
use App\Responses\DIalog\GetDialogByUserResponse;
use App\Responses\DIalog\SendMessageResponse;
use App\Config;

class DialogController extends Controller
{
    public function sendDialog(): void
    {
        $request = new SendMessageRequest();

        $data = $request->validation();

        $authUser = $request->getAuthUser();

        $storage = Config::get('dialog_storage') ?? 'postgres';
        if ($storage === 'redis') {
            $useCase = new CreateMessageRedis()->createMessage($authUser->id, $data);
        } else {
            $useCase = new CreateMessage()->createMessage($authUser->id, $data);
        }

        SendMessageResponse::create($useCase['messageId'], $useCase['dialogId'])->json();
    }

    public function getDialogByUser(): void
    {
        $request = new GetDialogByUserRequest();

        $data = $request->validation();
        $authUser = $request->getAuthUser();

        $storage = Config::get('dialog_storage') ?? 'postgres';
        if ($storage === 'redis') {
            $data = new GetDialogByTwoUsersRedis()->getDialog($authUser->id, $data['userId']);
        } else {
            $data = new GetDialogByTwoUsers()->getDialog($authUser->id, $data['userId']);
        }

        GetDialogByUserResponse::create($data)->json();
    }
}