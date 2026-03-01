Для шардирования диалогов, добавил три таблицы.

- dialogs (id, name)
Тут хранятся диалоги в которых состоят пользователи. Первичный ключ по полю id. Шардирование по полю id.

- dialog_messages (id, dialog_id, author_user_id,content)
Тут хранятся сообщения из диалогов. Первичный ключ по двум полям dialog_id и id.
Шардирование по полю dialog_id, для того чтобы диалоги и сообщения попали в одни шарды. 

- dialog_users (dialog_id, user_id)
Тут хранятся какие диалоги есть у пользователей. Первичный ключ dialog_id и user_id. Шардирование так же по полю dialog_id.

Вынес сервис диалогов в отдельную подсистему в монолите. С отбельной БД в которой настроено шардирование. 
Все запускается через docker_compose из корня приложения.

Шардирование выполнил по шагам из вебинара, но изменил docker-compose т.к. образ citusdata/membership-manager:0.3.0
устарел и не запускается в новой версии докер.

Добавил в docker-compose файл сервисы для создания базы для диалогов с шардированием.

Добавил два апи метода для отправки сообщения в чат и получения сообщений.
Постман коллекция лежит в корне проекта social_network.postman_collection.json

Запуск проекта.

1. Выполнить сборку образов и запустить контейнеры.
````
docker compose up --build -d
````
2. Войти в контейнер php. Выполнить миграцю схемы.
````
docker-compose exec php bash

php migrate.php
````
В результате миграции создадутся таблицы для базы main и базы диалогов.
В диалогах выполнятся запросы на создание шардов (create_distributed_table).

3. Войти в контейнер citus_master. Добавить узлы для воркеров.
````
docker exec -it citus_master psql -U postgres

SELECT citus_set_coordinator_host('master', 5432);

SELECT * from citus_add_node('citus_worker_1', 5432);
SELECT * from citus_add_node('citus_worker_2', 5432);

-- Проверяем, что узлы добавились:
SELECT * FROM citus_get_active_worker_nodes();
````

4. В docker compose сначало запускается два воркера. Что бы добавить еще воркеров нужно

- Раскомментировать в docker compose worker3 и worker4. Запустить:
````
docker-compose up -d worker3 worker4 
````
- Войти в мастер docker exec -it citus_master psql -U postgres и выполнить 
````
SELECT * from citus_add_node('citus_worker_3', 5432);
SELECT * from citus_add_node('citus_worker_4', 5432);

alter system set wal_level = logical;
SELECT run_command_on_workers('alter system set wal_level = logical');
````

- Перезапустить контейнеры
````
exit
docker-compose restart
````

- Сделать ребалансировку.
````
docker exec -it citus-worker-1 psql -U postgres
show wal_level;

SELECT citus_rebalance_start();

SELECT * FROM citus_rebalance_status();
````