<?php

namespace App;

use App\Controller\AuthController;
use App\Controller\UserController;
use App\Middlewares\AuthMiddelware;

class Router
{
    public function routing(): void
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        if ($requestUri === '/login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new AuthController();
            $controller->login();
        } elseif ($requestUri === '/user/register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller = new UserController();
            $controller->userRegister();
        } elseif ($requestUri === '/user/get' && $_SERVER['REQUEST_METHOD'] === 'GET') {

            AuthMiddelware::execute();

            $controller = new UserController();
            $controller->userGet();
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not Found']);
        }
    }
}