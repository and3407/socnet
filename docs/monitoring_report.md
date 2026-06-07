# Отчет по домашнему заданию «Мониторинг»

## Цель
Организовать мониторинг сервиса чатов с использованием Prometheus, Zabbix и Grafana.

## Выполненные работы

### 1. Развертывание Zabbix
- Добавлены сервисы в docker-compose.yml:
  - `zabbix-server` (с базой данных PostgreSQL)
  - `zabbix-web` (веб-интерфейс на порту 8081)
  - `zabbix-agent` (агент для сбора технических метрик)
- Настроена база данных `zabbix` в контейнере `pgmain`.
- Zabbix сервер и веб-интерфейс доступны по адресам:
  - Zabbix Server: `localhost:10051`
  - Zabbix Web: `http://localhost:8081` (логин `Admin`, пароль `zabbix`)

### 2. Развертывание Prometheus
- Добавлен сервис `prometheus` в docker-compose.yml (порт 9090).
- Создана конфигурация `monitoring/prometheus/prometheus.yml` с job’ами:
  - `prometheus` – сам мониторинг
  - `node-exporter` – сбор системных метрик
  - `php-app` – сбор бизнес-метрик приложения
- Prometheus доступен по адресу `http://localhost:9090`.

### 3. Развертывание Grafana
- Добавлен сервис `grafana` в docker-compose.yml (порт 3000).
- Настроен provisioning для автоматического добавления источника данных (Prometheus) и дашбордов.
- Создан дашборд «RED Metrics - Chat Service» с панелями:
  - Rate (количество запросов в секунду)
  - Errors (количество ошибок)
  - Duration (длительность запросов)
  - Системные метрики (CPU, память)
- Grafana доступна по адресу `http://localhost:3000` (логин `admin`, пароль `admin`).

### 4. Интеграция бизнес-метрик RED в приложение
- Добавлена зависимость `promphp/prometheus_client_php` в composer.json.
- Создан класс `App\Metrics\PrometheusMetrics` для записи и экспорта метрик в формате Prometheus.
- Метрики хранятся в Redis (используется существующий Redis клиент).
- В роутер приложения добавлен эндпоинт `/metrics` (GET), который возвращает метрики.
- При каждом запросе к API увеличиваются счетчики:
  - `http_requests_total` – общее количество запросов по endpoint, методу и статусу
  - `http_request_duration_seconds` – длительность запроса
  - `http_errors_total` – количество ошибок
- Метрики доступны по адресу `http://localhost:8383/metrics`.

### 5. Сбор технических метрик через Zabbix
- Zabbix агент настроен на мониторинг контейнера `socnet_php`.
- Агент собирает стандартные системные метрики (CPU, память, диски, сеть).
- Метрики передаются на Zabbix сервер для дальнейшей обработки и алертинга.

### 6. Проверка работы мониторинга
- Все сервисы запущены и здоровы (проверка через `docker-compose ps`).
- Prometheus успешно собирает метрики с node-exporter и php-app (все targets в статусе `up`).
- Эндпоинт `/metrics` возвращает корректные данные:
  - `http_requests_total` – количество запросов по endpoint, методу и статусу
  - `http_errors_total` – количество ошибок по типу
  - `http_request_duration_seconds_avg` и `http_request_duration_seconds_count` – средняя длительность и количество измерений
  - `php_info` – информация о версии PHP
- Исправлена ошибка парсинга ключей Redis (неверное количество частей), теперь все метрики выводятся корректно.
- Grafana отображает дашборд с метриками (после настройки источника данных).

## Скриншоты (рекомендуется сделать самостоятельно)

1. **Prometheus Targets** – страница `http://localhost:9090/targets` с состоянием `up` для всех job’ов.
2. **Prometheus Graph** – запрос `http_requests_total` в Prometheus UI.
3. **Grafana Dashboard** – дашборд «RED Metrics - Chat Service» после нескольких запросов к API.
4. **Zabbix Web Interface** – главная страница Zabbix с данными о хосте `socnet_php`.
5. **Эндпоинт /metrics** – вывод `curl http://localhost:8383/metrics`.

## Инструкция по запуску

1. Убедитесь, что Docker и Docker Compose установлены.
2. В корне проекта выполните:
   ```bash
   docker-compose up -d
   ```
3. Дождитесь запуска всех контейнеров (может занять несколько минут).
4. Откройте в браузере:
   - Prometheus: http://localhost:9090
   - Grafana: http://localhost:3000 (логин `admin`, пароль `admin`)
   - Zabbix: http://localhost:8081 (логин `Admin`, пароль `zabbix`)
5. Для генерации нагрузки на приложение используйте утилиту `ab` или выполните несколько запросов к API:
   ```bash
   curl http://localhost:8383/metrics
   curl -X POST http://localhost:8383/user/register ...
   ```

## Заключение
Мониторинг сервиса чатов настроен в полном объеме:
- **Prometheus** собирает бизнес-метрики по принципу RED (Rate, Errors, Duration).
- **Zabbix** отвечает за сбор технических метрик сервера.
- **Grafana** предоставляет единый дашборд для визуализации всех метрик.

Система готова к эксплуатации и позволяет оперативно отслеживать состояние сервиса.