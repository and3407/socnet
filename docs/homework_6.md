# Описание

Добавил в проект websocket сервер, RabbitMQ и ендпоинт по создании постов.

При создании поста в RabbitMQ создается очередь которая разбирается консюмеров.
Все друзья пользователя получают сообщения о создании нового поста.
Так же для всех друзей обновляется кеш в редисе.

Фронт часть не делал. Можно проверить через консоль сервиса socnet_rabbitmq_consumer.

Пример вывода:

````
 [*] Published to user:334:posts
 [*] Published to user:440:posts
 [*] Published to user:67:posts
 [*] Published to user:253:posts
 [*] Published to user:553:posts
 [*] Published to user:446:posts
 [*] Published to user:193:posts
 [*] Published to user:343:posts
 [*] Published to user:504:posts
 [*] Published to user:311:posts
 [*] Published to user:189:posts
 [*] Published to user:376:posts
 [*] Published to user:349:posts
 [*] Published to user:503:posts
 [*] Published to user:444:posts
 [*] Published to user:424:posts
 [*] Published to user:402:posts
 [*] Published to user:5:posts
 [*] Published to user:130:posts
 [*] Published to user:379:posts
 [*] Published to user:123:posts
 [*] Refreshing cache for friends: 481, 28, 372, 10, 300, 521, 329, 470, 279, 227, 197, 174, 540, 198, 
 352, 72, 126, 60, 56, 400, 360, 336, 390, 260, 257, 520, 116, 460, 346, 94, 57, 171, 532, 516, 315, 337, 
 547, 224, 183, 466, 430, 210, 404, 450, 230, 128, 34, 478, 347, 515, 399, 102, 369, 358, 410, 177, 125,
  154, 118, 326, 278, 119, 199, 122, 29, 500, 162, 469, 438, 15, 334, 440, 67, 253, 553, 446, 193, 343, 
  504, 311, 189, 376, 349, 503, 444, 424, 402, 5, 130, 379, 123
 [*] Cache refreshed
````

## Запуск проекта 

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