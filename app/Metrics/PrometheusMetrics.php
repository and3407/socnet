<?php

namespace App\Metrics;

use App\Domain\Redis\RedisClient;

class PrometheusMetrics
{
    private RedisClient $redis;

    public function __construct()
    {
        $this->redis = new RedisClient();
    }

    /**
     * Увеличить счетчик запросов по endpoint и методу
     */
    public function incRequestCounter(string $endpoint, string $method, int $statusCode = 200): void
    {
        $key = "metrics:requests:{$endpoint}:{$method}:{$statusCode}";
        $this->redis->client->incr($key);
    }

    /**
     * Записать длительность запроса
     */
    public function observeRequestDuration(string $endpoint, string $method, float $durationSeconds): void
    {
        $key = "metrics:durations:{$endpoint}:{$method}";
        $this->redis->client->rPush($key, $durationSeconds);
        // Ограничим список последних 100 значений
        $this->redis->client->lTrim($key, -100, -1);
    }

    /**
     * Увеличить счетчик ошибок
     */
    public function incErrorCounter(string $endpoint, string $method, string $errorType): void
    {
        $key = "metrics:errors:{$endpoint}:{$method}:{$errorType}";
        $this->redis->client->incr($key);
    }

    /**
     * Генерация метрик в формате Prometheus
     */
    public function renderMetrics(): string
    {
        $output = [];
        $redis = $this->redis->client;

        // Счетчики запросов
        $keys = $redis->keys('metrics:requests:*');
        foreach ($keys as $key) {
            $value = $redis->get($key);
            if ($value === false) {
                continue;
            }
            // Парсим ключ
            $parts = explode(':', $key);
            if (count($parts) !== 5) {
                continue;
            }
            $endpoint = $parts[2];
            $method = $parts[3];
            $status = $parts[4];
            $metricName = 'http_requests_total';
            $labels = sprintf('endpoint="%s",method="%s",status="%s"', $endpoint, $method, $status);
            $output[] = sprintf('%s{%s} %d', $metricName, $labels, $value);
        }

        // Средняя длительность (упрощенно)
        $durationKeys = $redis->keys('metrics:durations:*');
        foreach ($durationKeys as $key) {
            $values = $redis->lRange($key, 0, -1);
            if (empty($values)) {
                continue;
            }
            $sum = 0;
            $count = count($values);
            foreach ($values as $v) {
                $sum += (float)$v;
            }
            $avg = $count > 0 ? $sum / $count : 0;
            $parts = explode(':', $key);
            if (count($parts) !== 4) {
                continue;
            }
            $endpoint = $parts[2];
            $method = $parts[3];
            $metricName = 'http_request_duration_seconds';
            $labels = sprintf('endpoint="%s",method="%s"', $endpoint, $method);
            $output[] = sprintf('%s{%s} %f', $metricName . '_avg', $labels, $avg);
            $output[] = sprintf('%s{%s} %d', $metricName . '_count', $labels, $count);
        }

        // Счетчики ошибок
        $errorKeys = $redis->keys('metrics:errors:*');
        foreach ($errorKeys as $key) {
            $value = $redis->get($key);
            if ($value === false) {
                continue;
            }
            $parts = explode(':', $key);
            if (count($parts) !== 5) {
                continue;
            }
            $endpoint = $parts[2];
            $method = $parts[3];
            $errorType = $parts[4];
            $metricName = 'http_errors_total';
            $labels = sprintf('endpoint="%s",method="%s",error_type="%s"', $endpoint, $method, $errorType);
            $output[] = sprintf('%s{%s} %d', $metricName, $labels, $value);
        }

        // Добавим системные метрики (фиктивные)
        $output[] = '# HELP php_info Information about the PHP environment';
        $output[] = '# TYPE php_info gauge';
        $output[] = 'php_info{version="' . phpversion() . '"} 1';

        return implode("\n", $output);
    }
}