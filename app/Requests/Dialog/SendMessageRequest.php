<?php

namespace App\Requests\Dialog;

use App\Requests\Request;
use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class SendMessageRequest extends Request
{
    public function validation(): array
    {
        $data = $this->getJsonData();

        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        $pattern = '#^/dialog/(\d+)/send$#';

        if (preg_match($pattern, $requestUri, $matches)) {
            $recipientUserId = (int)$matches[1];
        }

        if (empty($recipientUserId)) {
            ErrorResponse::createJson('Invalid parameters', HttpCode::BAD_REQUEST, ['Invalid user id']);
        }

        return [
            'recipientUserId' => $recipientUserId,
            'content' => $data['text'],
        ];
    }
}