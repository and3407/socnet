<?php

namespace App\Middlewares;

use App\Domain\Token\Repositories\TokenRepository;
use App\Domain\User\Repositories\UserRepository;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class AuthMiddelware
{
    protected TokenRepository $tokenRepository;
    protected UserRepository $userRepository;

    public function __construct()
    {
        $this->tokenRepository = new TokenRepository();
        $this->userRepository = new UserRepository();
    }

    public static function execute(): void
    {
        $middelware = new self();

        $middelware->checkAuth();
    }

    public function checkAuth(): void
    {
        $token = $this->getBearerToken();

        if ($this->isValidateToken($token)) {
            $token = $this->tokenRepository->getTokenByToken($token);
            $user = $this->userRepository->getUserById($token->userid);

            if ($user === null) {
                ErrorResponse::createJson('Authorization header missing or invalid', HttpCode::UNAUTHORIZED);
            }

            session_start();

            $_SESSION['authUserId'] = $user->id;

            return;
        }

        ErrorResponse::createJson('Authorization header missing or invalid', HttpCode::UNAUTHORIZED);
    }

    private function isValidateToken(string $apiToken): bool
    {
        $token = $this->tokenRepository->getTokenByToken($apiToken);

        return $token !== null;
    }

    private function getBearerToken(): ?string
    {
        $headers = $this->getAuthorizationHeader();

        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function getAuthorizationHeader(): ?string
    {
        // Проверяем стандартный заголовок Authorization
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        // Проверяем альтернативные варианты (для некоторых серверов)
        if (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();

            // Обрабатываем заголовки с разным регистром
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );

            if (isset($requestHeaders['Authorization'])) {
                return trim($requestHeaders['Authorization']);
            }
        }

        // Проверяем заголовок в другом формате (для некоторых серверов)
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        return null;
    }
}