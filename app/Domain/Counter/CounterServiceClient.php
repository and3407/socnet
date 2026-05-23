<?php

namespace App\Domain\Counter;

use App\Responses\ErrorResponse;
use App\Responses\HttpCode;

class CounterServiceClient
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = 'http://counter-service:9100/api/v1';
    }

    /**
     * Get total unread count for user
     */
    public function getTotalUnread(int $userId): int
    {
        $url = $this->baseUrl . '/counters/total';

        $headers = [
            'X-User-Id: ' . $userId,
            'X-Request-Id: ' . $this->getRequestId(),
        ];

        $response = $this->makeHttpRequest('GET', $url, [], $headers);

        if ($response['http_code'] !== 200) {
            // Если сервис недоступен, возвращаем 0, чтобы не ломать UX
            error_log('Counter service error: ' . json_encode($response));
            return 0;
        }

        return $response['body']['total_unread'] ?? 0;
    }

    /**
     * Get unread counts for all dialogs of user
     */
    public function getDialogUnreads(int $userId): array
    {
        $url = $this->baseUrl . '/counters/dialogs';

        $headers = [
            'X-User-Id: ' . $userId,
            'X-Request-Id: ' . $this->getRequestId(),
        ];

        $response = $this->makeHttpRequest('GET', $url, [], $headers);

        if ($response['http_code'] !== 200) {
            return [];
        }

        return $response['body']['dialogs'] ?? [];
    }

    /**
     * Get unread count for specific dialog
     */
    public function getDialogUnread(int $userId, int $dialogId): int
    {
        $url = $this->baseUrl . '/counters/dialogs/' . $dialogId;

        $headers = [
            'X-User-Id: ' . $userId,
            'X-Request-Id: ' . $this->getRequestId(),
        ];

        $response = $this->makeHttpRequest('GET', $url, [], $headers);

        if ($response['http_code'] !== 200) {
            return 0;
        }

        return $response['body']['unread_count'] ?? 0;
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
            return ['http_code' => 503, 'body' => []];
        }

        return [
            'http_code' => $httpCode,
            'body' => json_decode($response, true) ?? [],
        ];
    }
}