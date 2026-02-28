Запуск проекта.

1. Выполнить сборку образов и запустить контейнеры.
````
docker compose up --build --scale worker=3 -d
````

2. Войти в контейнер php. Выполнить миграцю схемы.
````
docker-compose exec php bash

php migrate.php
````