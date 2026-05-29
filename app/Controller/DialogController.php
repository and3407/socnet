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

    public function markDialogAsRead(): void
    {
        // Получаем dialogId из URL (параметр маршрута)
        $requestUri = $_SERVER['REQUEST_URI'];
        preg_match('#^/dialog/(\d+)/read$#', $requestUri, $matches);
        if (!isset($matches[1])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid dialog ID']);
            return;
        }
        $dialogId = (int) $matches[1];

        // Получаем аутентифицированного пользователя из сессии (установлено AuthMiddelware)
        session_start();
        $userId = $_SESSION['authUserId'] ?? null;
        if (!$userId) {
            http_response_code(401);
            echo json_encode(['error' => 'User not authenticated']);
            return;
        }

        $client = new DialogServiceClient();
        $result = $client->markDialogAsRead((int) $userId, $dialogId);

        // Возвращаем успешный ответ
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
    }
}