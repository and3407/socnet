<?php

namespace App\Responses;

abstract class Response
{
    public function successResponse(array $data, int $code = 200): void
    {
        http_response_code($code);

        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        // exit removed to allow metrics collection
    }

    public function errorResponse(string $message, array $data = [], int $code = 500): void
    {
        http_response_code($code);

        header('Content-Type: application/json; charset=utf-8');

        $result = [
            'message' => $message,
            'code' => $code,
            'data' => $data,
        ];

        echo json_encode($result, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        // exit removed to allow metrics collection
    }
}