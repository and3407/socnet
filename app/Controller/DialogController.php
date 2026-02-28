<?php

namespace App\Controller;

use App\Domain\Dialog\Usecases\CreateMessage;
use App\Domain\Dialog\Usecases\GetDialogByTwoUsers;
use App\Requests\Dialog\GetDialogByUserRequest;
use App\Requests\Dialog\SendMessageRequest;
use App\Responses\DIalog\GetDialogByUserResponse;
use App\Responses\DIalog\SendMessageResponse;

class DialogController extends Controller
{
    public function sendDialog(): void
    {
        $request = new SendMessageRequest();

        $data = $request->validation();

        $authUser = $request->getAuthUser();

        $useCase = new CreateMessage()->createMessage($authUser->id, $data);

        SendMessageResponse::create($useCase['messageId'], $useCase['dialogId'])->json();
    }

    public function getDialogByUser(): void
    {
        $request = new GetDialogByUserRequest();

        $data = $request->validation();
        $authUser = $request->getAuthUser();

        $data = new GetDialogByTwoUsers()->getDialog($authUser->id, $data['userId']);

        GetDialogByUserResponse::create($data)->json();
    }
}