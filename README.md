# Иструкции

## Инструкция для локальной среды разработки

1. Выполнить сборку образов и запустить контейнеры.
````
docker-compose up --build -d
````

2. Войти в контейнер php. Выполнить комманды.
````
docker-compose exec php bash

composer install

Миграции php migrate.php
````

Заполнить users 
```` 
сd app/Database/migrations/upload_users 
php run.php
````

Заполнить posts
```` 
сd app/Database/migrations/upload_posts
php run.php
````

Заполнить user_friends
```` 
сd app/Database/migrations/upload_friends
php run.php
````

3. Postman коллекция в корне проекта ```` social_network.postman_collection.json ````

Перменные коллекции:
- host ```` http://localhost:8080 ````
- auth_token ```` Bearer toker - подставляется автоматом при вызове /login ````


4. При регистрации выдается uuid. Спомощью uuid можно получить token и данные по анкете через метод ```` /user/get?uuid=e8e5c45a-33e9-4841-b944-6396bd1c9b49 ````


## База данных
При запуске приложения создается база данных ````pgmain````
Что бы приложение работало с ````pgmain````, здесь ````\App\Database\Db::createConnection```` нужно расскомментировать  ````$configs = $configsDatabase['common'];````

Приложение настроено работать с репликацией. 
Что бы приложение работало с репликацией, нужно запустить реплики по инструкции ````docs/postgresql_replication.md````

## Балансировка бекенда

Приложение теперь запускается с тремя экземплярами PHP-FPM (php1, php2, php3) для горизонтального масштабирования. Nginx настроен как балансировщик нагрузки с upstream `php_backend`, распределяющий запросы между этими экземплярами по алгоритму round-robin.

Проверить балансировку можно через тестовый эндпоинт: `http://localhost:8383/balance-test.php`. Он возвращает hostname контейнера, обработавшего запрос.

Конфигурация:
- `docker-compose.yml` содержит сервисы php1, php2, php3.
- `docker/nginx/default.conf` включает upstream блок с тремя серверами.

Для добавления дополнительных экземпляров PHP достаточно добавить новые сервисы в docker-compose и обновить upstream в конфиге nginx.

