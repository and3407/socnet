#!/bin/bash
set -e

# Ожидаем, что мастер будет готов
until pg_isready -h pgmaster -p 5432; do
  echo "Waiting for master to be ready..."
  sleep 2
done

# Очищаем данные, если они есть (для чистого старта)
rm -rf /var/lib/postgresql/data/*

# Выполняем базовое резервное копирование с мастера
PGPASSWORD=replicator_password pg_basebackup -h pgmaster -p 5432 -U replicator -D /var/lib/postgresql/data -Fp -Xs -R

# Создаем файл standby.signal для активации режима standby
touch /var/lib/postgresql/data/standby.signal

# Определяем слот репликации на основе имени хоста
HOSTNAME=$(hostname)
if [[ "$HOSTNAME" == "pgslave1" ]]; then
  SLOT_NAME="replication_slot_slave1"
elif [[ "$HOSTNAME" == "pgslave2" ]]; then
  SLOT_NAME="replication_slot_slave2"
else
  echo "Unknown hostname $HOSTNAME, defaulting to replication_slot_slave1"
  SLOT_NAME="replication_slot_slave1"
fi

# Настраиваем postgresql.conf для репликации
cat >> /var/lib/postgresql/data/postgresql.conf << EOF
primary_conninfo = 'host=pgmaster port=5432 user=replicator password=replicator_password'
primary_slot_name = '$SLOT_NAME'
hot_standby = on
EOF

echo "Slave initialization complete with slot $SLOT_NAME."