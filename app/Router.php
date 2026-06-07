<?php

namespace App;

use App\Controller\AuthController;
use App\Controller\DialogController;
use App\Controller\PostController;
use App\Controller\UserController;
use App\Middlewares\AuthMiddelware;
use App\Metrics\PrometheusMetrics;

class Router
{
    private UserController $userController;
    private DialogController $dialogController;

    private PostController $postController;
    private PrometheusMetrics $metrics;

    public function __construct()
    {
        $this->userController = new UserController();
        $this->dialogController = new DialogController();
        $this->postController = new PostController();
        $this->metrics = new PrometheusMetrics();
    }

    public function routing(): void
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $method = $_SERVER['REQUEST_METHOD'];

        // Start timing
        $startTime = microtime(true);

        // Metrics endpoint
        if ($requestUri === '/metrics' && $method === 'GET') {
            header('Content-Type: text/plain; version=0.0.4');
            echo $this->metrics->renderMetrics();
            return;
        }

        // Handle other routes
        $statusCode = 200;
        try {
            if ($requestUri === '/login' && $method === 'POST') {
                $controller = new AuthController();
                $controller->login();
            } elseif ($requestUri === '/user/register' && $method === 'POST') {
                $controller = new UserController();
                $controller->userRegister();
            } elseif ($requestUri === '/user/get' && $method === 'GET') {
                AuthMiddelware::execute();

                $this->userController->userGet();
            } elseif ($requestUri === '/user/search' && $method === 'GET') {
                AuthMiddelware::execute();

                $this->userController->userSearch();
            } elseif($requestUri === '/user/posts' && $method === 'GET') {
                AuthMiddelware::execute();

                $this->userController->getUserPosts();
            } elseif (preg_match('#^/dialog/(\d+)/send$#', $requestUri) && $method === 'POST') {
                AuthMiddelware::execute();

                $this->dialogController->sendDialog();
            } elseif(preg_match('#^/dialog/(\d+)/list$#', $requestUri) && $method === 'GET') {
                AuthMiddelware::execute();

                $this->dialogController->getDialogByUser();
            } elseif ($requestUri === '/post/create' && $method === 'POST') {
                AuthMiddelware::execute();

                $this->postController->createPost();
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Not Found']);
                $statusCode = 404;
            }
        } catch (\Throwable $e) {
            $statusCode = 500;
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Internal Server Error']);
        }

        // Record metrics
        $duration = microtime(true) - $startTime;
        $this->metrics->incRequestCounter($requestUri, $method, $statusCode);
        $this->metrics->observeRequestDuration($requestUri, $method, $duration);
        if ($statusCode >= 400) {
            $this->metrics->incErrorCounter($requestUri, $method, 'http_' . $statusCode);
        }
    }
}