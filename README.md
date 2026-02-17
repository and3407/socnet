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

