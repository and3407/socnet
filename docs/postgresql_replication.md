# Репликация в PostgreSQL
## Физическая репликация
1. Запоминаем адрес сети. Сеть создается при запуске проекта
    ```shell
    docker network inspect socnet_socnet-network | grep Subnet # Запомнить маску сети
    ```

2. Поднимаем мастер
    ```shell
    docker run -dit -v "$PWD/volumes/pgmaster/:/var/lib/postgresql/data" -e POSTGRES_PASSWORD=admin -p "5440:5432" --restart=unless-stopped --network=socnet_socnet-network --name=pgmaster postgres:17
    ```

3. Меняем postgresql.conf на мастере
    ```conf
    ssl = off
    wal_level = replica
    max_wal_senders = 4 # expected slave num
    ```

4. Подключаемся к мастеру и создаем пользователя для репликации
    ```shell
    docker exec -it pgmaster su - postgres -c psql
    create role replicator with login replication password 'admin';
    exit
    ```

5. Добавляем запись в `pgmaster/pg_hba.conf` с `subnet` с первого шага
    ```
    host    replication     replicator       172.19.0.0/16          md5
    ```

6. Перезапустим мастер
    ```shell
    docker restart pgmaster
    ```

7. Сделаем бэкап для реплик
    ```shell
    docker exec -it pgmaster bash
    mkdir /pgslave1
    pg_basebackup -h pgmaster -D /pgslave1 -U replicator -v -P --wal-method=stream
    exit
    ```

8. Копируем директорию себе
    ```shell
    docker cp pgmaster:/pgslave1 volumes/pgslave1/
    ```

9. Создадим файл, чтобы реплика узнала, что она реплика
    ```shell
    touch volumes/pgslave1/standby.signal
    ```

10. Меняем `postgresql.conf` на реплике `pgslave1`
    ```conf
    primary_conninfo = 'host=pgmaster port=5432 user=replicator password=admin application_name=pgslave1'
    ```

11. Запускаем реплику `pgslave1`
    ```shell
    docker run -dit -v "$PWD/volumes/pgslave1/:/var/lib/postgresql/data" -e POSTGRES_PASSWORD=admin -p "15432:5432" --network=socnet_socnet-network --restart=unless-stopped --name=pgslave1 postgres:17
    ```

12. Запустим вторую реплику `pgslave2`
    - скопируем бэкап
        ```shell
        docker cp pgmaster:/pgslave1 volumes/pgslave2/
        ```

    - изменим настройки `pgslave2/postgresql.conf`
        ```conf
        primary_conninfo = 'host=pgmaster port=5432 user=replicator password=admin application_name=pgslave2'
        ```

    - дадим знать что это реплика
        ```shell
        touch volumes/pgslave2/standby.signal
        ```

    - запустим реплику `pgslave2`
        ```shell
        docker run -dit -v "$PWD/volumes/pgslave2/:/var/lib/postgresql/data" -e POSTGRES_PASSWORD=admin -p "25432:5432" --network=socnet_socnet-network --restart=unless-stopped --name=pgslave2 postgres:17
        ```

14. Убеждаемся что обе реплики работают в асинхронном режиме на `pgmaster`
    ```shell
    docker exec -it pgmaster su - postgres -c psql
    select application_name, sync_state from pg_stat_replication;
    exit;
    ```

15. Включаем синхронную репликацию на `pgmaster`
    - меняем файл `pgmaster/postgresql.conf`
        ```conf
        synchronous_commit = on
        synchronous_standby_names = 'FIRST 1 (pgslave, pgasyncslave)'
        ```

    - перечитываем конфиг
        ```shell
        docker exec -it pgmaster su - postgres -c psql
        select pg_reload_conf();
        exit;
        ```

16. Убеждаемся, что реплика стала синхронной
    ```shell
    docker exec -it pgmaster su - postgres -c psql
    select application_name, sync_state from pg_stat_replication;
    exit;
    ```

17. Создадим тестовую таблицу на `pgmaster` и проверим репликацию
    ```shell
    docker exec -it pgmaster su - postgres -c psql
    create table test(id bigint primary key not null);
    insert into test(id) values(1);
    select * from test;
    exit;
    ```

18. Проверим наличие данных на `pgslave1`
    ```shell
    docker exec -it pgslave1 su - postgres -c psql
    select * from test;
    exit;
    ```

19. Проверим наличие данных на `pgslave2`
    ```shell
    docker exec -it pgslave2 su - postgres -c psql
    select * from test;
    exit;
    ```
20. Попробуем сделать `insert` на `pgslave1`
    ```shell
    docker exec -it pgslave1 su - postgres -c psql
    insert into test(id) values(2);
    exit;
    ```
21. Укладываем репилку `pgslave2` и проверяем работу `pgmaster` и `pgslave1`
    ```shell
    docker stop pgasyncslave
    docker exec -it pgmaster su - postgres -c psql
    select application_name, sync_state from pg_stat_replication;
    insert into test(id) values(2);
    select * from test;
    exit;
    docker exec -it pgslave1 su - postgres -c psql
    select * from test;
    exit;
    ```
22. Укладываем репилку `pgslave1` и проверяем работу `pgmaster`, а потом возвращаем реплику `pgslave1`
    - terminal 1
        ```shell
        docker stop pgslave
        docker exec -it pgmaster su - postgres -c psql
        select application_name, sync_state from pg_stat_replication;
        insert into test(id) values(3);
        exit;
        ```
    - terminal 2
        ```shell
        docker start pgslave1
        ```
23. Возвращаем вторую реплику `pgslave2`
    ```shell
    docker start pgslave2
    ```
24. Убиваем мастер `pgmaster`
    ```shell
    docker stop pgmaster
    ```
25. Запромоутим реплику `pgslave1`
    ```shell
    docker exec -it pgslave1 su - postgres -c psql
    select pg_promote();
    exit;
    ```
26. Пробуем записать в новый мастер `pgslave1`
    ```shell
    docker exec -it pgslave su - postgres -c psql
    insert into test(id) values(4);
    exit;
    ```

27. Настраиваем репликацию на `pgslave1` (`pgslave/postgresql.conf`)
    - изменяем конфиг
        ```conf
        synchronous_commit = on
        synchronous_standby_names = 'ANY 1 (pgmaster, pgslave2)'
        ```
    - перечитываем конфиг
        ```shell
        docker exec -it pgslave1 su - postgres -c psql
        select pg_reload_conf();
        exit;
        ```

28. Подключим вторую реплику `pgslave2` к новому мастеру `pgslave`
    - изменяем конфиг `pgslave2/postgresql.conf`
        ```conf
        primary_conninfo = 'host=pgslave1 port=5432 user=replicator password=pass application_name=pgslave2'
        ```
    - перечитываем конфиг
        ```shell
        docker exec -it pgslave2 su - postgres -c psql
        select pg_reload_conf();
        exit;
        ```
29. Проверяем что к новому мастеру `pgslave1` подключена реплика и она работает
    ```shell
    docker exec -it pgslave1 su - postgres -c psql
    select application_name, sync_state from pg_stat_replication;
    insert into test(id) values (5)
    select * from test;
    exit;
    docker exec -it pgslave2 su - postgres -c psql
    select * from test;
    exit;
    ```
30. Восстановим старый мастер `pgmaster` как реплику
    1. Помечаем как реплику
        ```shell
        touch volumes/pgmaster/standby.signal
        ```
    2. Изменяем конфиг `pgmaster/postgresql.conf`
        ```conf
        primary_conninfo = 'host=pgslave1 port=5432 user=replicator password=admin application_name=pgmaster'
        ```
    3. Запустим `pgmaster`
       ```shell
        docker start pgmaster
        ```
    4. Убедимся что `pgmaster` подключился как реплика к `pgslave1`
        ```shell
        docker exec -it pgslave1 su - postgres -c psql
        select application_name, sync_state from pg_stat_replication;
        exit;
        ```
