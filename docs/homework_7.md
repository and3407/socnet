# Описание

Сделал создания сообщения чата и получения сообщений чата через redis.

Контроллер \App\Controller\DialogController

В app/Config.php ``` 'dialog_storage' => 'redis', // 'postgres' или 'redis' ```
Можно переключить где хранить сообщения. Использовал при нагрузочном тестировании. 

Вынес логику в UDF
app/Domain/Dialog/RedisScripts/create_dialog.lua
app/Domain/Dialog/RedisScripts/create_message.lua

Нагрузочное тестирование выполнил через утилиту ab (Apache Bench)
- Количество запросов: 1000
- 10 одновременных запросов

Результат. Производительность Redis значительно выше. При Redis пропусканая способость выше примерно 30%, время обработки уменьшилось в 4.2 раза.


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

3. Postman коллекция в корне проекта ```` social_network.postman_collection.json ````

Перменные коллекции:
- host ```` http://localhost:8080 ````
- auth_token ```` Bearer toker - подставляется автоматом при вызове /login ````


- Ендпоинт создания сообщения и чата ``` dialog/{user_id}/send ```
- Получения сообщений чата ``` /dialog/{user_id}/list ```