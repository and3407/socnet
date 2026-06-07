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
        
        // Гистограмма: инкремент бакетов
        $buckets = [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10];
        foreach ($buckets as $bucket) {
            if ($durationSeconds <= $bucket) {
                $bucketKey = "metrics:histogram:{$endpoint}:{$method}:{$bucket}";
                $this->redis->client->incr($bucketKey);
            }
        }
        // Инкремент общего счетчика +Inf
        $infKey = "metrics:histogram:{$endpoint}:{$method}:inf";
        $this->redis->client->incr($infKey);
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
        // Отключаем вывод ошибок, чтобы не портить формат Prometheus
        $oldErrorReporting = error_reporting(0);
        $oldDisplayErrors = ini_set('display_errors', '0');
        
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
            $output[] = sprintf('%s{%s} %f', $metricName . '_sum', $labels, $sum);
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

        // Гистограмма длительностей
        $histogramKeys = $redis->keys('metrics:histogram:*');
        $bucketValues = [];
        foreach ($histogramKeys as $key) {
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
            $bucket = $parts[4];
            $bucketValues[$endpoint][$method][$bucket] = $value;
        }
        // Вывод бакетов
        foreach ($bucketValues as $endpoint => $methods) {
            foreach ($methods as $method => $buckets) {
                // Сортировка бакетов по значению le
                $sortedBuckets = [];
                foreach ($buckets as $bucket => $count) {
                    if ($bucket === 'inf') {
                        $sortedBuckets['+Inf'] = $count;
                    } else {
                        // Сохраняем как строку, но для сортировки используем числовое значение
                        $sortedBuckets[$bucket] = $count;
                    }
                }
                // Сортируем по числовому значению бакета, '+Inf' в конец
                uksort($sortedBuckets, function ($a, $b) {
                    if ($a === '+Inf') return 1;
                    if ($b === '+Inf') return -1;
                    return (float)$a <=> (float)$b;
                });
                $cumulative = 0;
                foreach ($sortedBuckets as $le => $count) {
                    $cumulative += $count;
                    $leLabel = $le === '+Inf' ? '+Inf' : sprintf('%.3f', (float)$le);
                    $metricName = 'http_request_duration_seconds_bucket';
                    $labels = sprintf('endpoint="%s",method="%s",le="%s"', $endpoint, $method, $leLabel);
                    $output[] = sprintf('%s{%s} %d', $metricName, $labels, $cumulative);
                }
                // Добавим сумму и количество (уже есть в секции длительностей)
            }
        }

        // Добавим системные метрики (фиктивные)
        $output[] = '# HELP php_info Information about the PHP environment';
        $output[] = '# TYPE php_info gauge';
        $output[] = 'php_info{version="' . phpversion() . '"} 1';

        // Восстанавливаем настройки ошибок
        error_reporting($oldErrorReporting);
        if ($oldDisplayErrors !== false) {
            ini_set('display_errors', $oldDisplayErrors);
        }

        return implode("\n", $output);
    }
}