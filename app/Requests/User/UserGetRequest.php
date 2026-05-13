<?php

namespace App\Requests\User;

use App\Domain\User\Repositories\UserRepository;
use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;
use PDO;

class UserGetRequest extends Request
{
    public function validation(): array
    {
        $data = $this->getQueryParams();

        if (!$data['uuid']) {
            ErrorResponse::createJson('Invalid parameters.', HttpCode::BAD_REQUEST);
        }

        $user = $this->getUserRepository()->getUserByUuid($data['uuid']);


        // Тестовый скрипт для проверки балансировки
        $db = new PDO('pgsql:host=haproxy;port=5433;dbname=postgres', 'postgres', 'admin');

        // Узнаем, на какой слейв мы попали через HAProxy
        $stmt = $db->query("SELECT inet_server_addr() as server_ip, inet_client_addr() as client_ip");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $stat = [
            'IP сервера (слейва)' => $result['server_ip'],
            'IP клиента (HAProxy)' => $result['client_ip'],
            'Сервер' => gethostname(),
        ];

        if ($user === null) {
            ErrorResponse::createJson('User not found', HttpCode::NOT_FOUND);
        }

        return [
            'user' => $user,
            'stat' => $stat,
        ];
    }

    protected function getUserRepository(): UserRepository
    {
        return new UserRepository();
    }
}