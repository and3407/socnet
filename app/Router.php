<?php

namespace App;

use App\Controller\AuthController;
use App\Controller\DialogController;
use App\Controller\PostController;
use App\Controller\UserController;
use App\Middlewares\AuthMiddelware;

class Router
{
    private UserController $userController;
    private DialogController $dialogController;

    private PostController $postController;

    public function __construct()
    {
        $this->userController = new UserController();
        $this->dialogController = new DialogController();
        $this->postController = new PostController();
    }

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

            $this->userController->userGet();
        } elseif ($requestUri === '/user/search' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            AuthMiddelware::execute();

            $this->userController->userSearch();
        } elseif($requestUri === '/user/posts' && $_SERVER['REQUEST_METHOD'] === 'GET') {
            AuthMiddelware::execute();

            $this->userController->getUserPosts();
        } elseif (preg_match('#^/user/([^/]+)/unread-count$#', $requestUri) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            AuthMiddelware::execute();

            $this->userController->getUnreadCount();
        } elseif (preg_match('#^/dialog/(\d+)/send$#', $requestUri) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddelware::execute();

            $this->dialogController->sendDialog();
        } elseif(preg_match('#^/dialog/(\d+)/list$#', $requestUri) && $_SERVER['REQUEST_METHOD'] === 'GET') {
            AuthMiddelware::execute();

            $this->dialogController->getDialogByUser();
        } elseif ($requestUri === '/post/create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            AuthMiddelware::execute();

            $this->postController->createPost();
        } else {
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Not Found']);
        }
    }
}