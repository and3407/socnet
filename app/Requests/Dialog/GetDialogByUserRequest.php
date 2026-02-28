<?php

namespace App\Requests\Dialog;

use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class GetDialogByUserRequest extends Request
{
    public function validation(): array
    {
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $pattern = '#^/dialog/(\d+)/list$#';

        if (preg_match($pattern, $requestUri, $matches)) {
            $userId = (int)$matches[1];
        }

        if (empty($userId)) {
            ErrorResponse::createJson('Invalid parameters', HttpCode::BAD_REQUEST, ['Invalid user id']);
        }

        return [
            'userId' => $userId,
        ];
    }
}