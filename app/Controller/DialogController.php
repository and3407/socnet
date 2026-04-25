<?php

namespace App\Controller;

use App\Domain\Dialog\DialogServiceClient;
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

        $client = new DialogServiceClient();
        $result = $client->sendMessage($authUser->id, $data['recipientUserId'], $data['content']);

        SendMessageResponse::create($result['messageId'], $result['dialogId'])->json();
    }

    public function getDialogByUser(): void
    {
        $request = new GetDialogByUserRequest();

        $data = $request->validation();
        $authUser = $request->getAuthUser();

        $client = new DialogServiceClient();
        $messages = $client->getDialog($authUser->id, $data['userId']);

        GetDialogByUserResponse::create($messages)->json();
    }
}