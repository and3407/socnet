<?php

namespace App\Responses;

class ErrorResponse extends Response
{
    public static function createJson(
        string $message,
        HttpCode $code = HttpCode::INTERNAL_SERVER_ERROR,
        array $data = []
    ): void {
        new self()->errorResponse($message, $data, $code->value);
    }
}