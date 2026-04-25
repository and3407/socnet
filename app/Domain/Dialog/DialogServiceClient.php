<?php

namespace App\Domain\Dialog;

use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class DialogServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://dialog-service:9000/api/v1';
    }

    /**
     * Send message via dialog service
     */
    public function sendMessage(int $authorUserId, int $recipientUserId, string $content): array
    {
        $url = $this->baseUrl . '/messages';

        $data = [
            'recipient_user_id' => $recipientUserId,
            'content' => $content,
        ];

        $headers = [
            'Content-Type: application/json',
            'X-User-Id: ' . $authorUserId,
            'X-Request-Id: ' . $this->getRequestId(),
        ];

        $response = $this->makeHttpRequest('POST', $url, $data, $headers);

        if ($response['http_code'] !== 201) {
            ErrorResponse::createJson('Dialog service error', HttpCode::INTERNAL_SERVER_ERROR);
        }

        return $response['body'];
    }

    /**
     * Get dialog messages via dialog service
     */
    public function getDialog(int $currentUserId, int $otherUserId): array
    {
        $url = $this->baseUrl . '/dialogs/' . $otherUserId;

        $headers = [
            'X-User-Id: ' . $currentUserId,
            'X-Request-Id: ' . $this->getRequestId(),
        ];

        $response = $this->makeHttpRequest('GET', $url, [], $headers);

        if ($response['http_code'] !== 200) {
            ErrorResponse::createJson('Dialog service error', HttpCode::INTERNAL_SERVER_ERROR);
        }

        return $response['body'];
    }

    private function getRequestId(): string
    {
        $requestId = $_SERVER['HTTP_X_REQUEST_ID'] ?? '';
        if (empty($requestId)) {
            $requestId = uniqid('req_', true);
        }
        return $requestId;
    }

    private function makeHttpRequest(string $method, string $url, array $data, array $headers): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            error_log('CURL error: ' . $error);
            ErrorResponse::createJson('Service unavailable', HttpCode::SERVICE_UNAVAILABLE);
        }

        return [
            'http_code' => $httpCode,
            'body' => json_decode($response, true) ?? [],
        ];
    }
}