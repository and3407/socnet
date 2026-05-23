#!/bin/bash
set -e

PG_HOST="${DB_HOST:-pgmain}"
PG_PORT="${DB_PORT:-5432}"
PG_USER="${DB_USERNAME:-postgres}"
PG_PASSWORD="${DB_PASSWORD:-admin}"
DB_NAME="${DB_DATABASE:-counter_db}"

echo "Waiting for PostgreSQL to be ready..."
until PGPASSWORD=$PG_PASSWORD psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USER" -d postgres -c '\q' 2>/dev/null; do
  sleep 1
done

echo "Creating database $DB_NAME if not exists..."
PGPASSWORD=$PG_PASSWORD psql -h "$PG_HOST" -p "$PG_PORT" -U "$PG_USER" -d postgres <<-EOSQL
    SELECT 'CREATE DATABASE $DB_NAME'
    WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME')\gexec
EOSQL

echo "Database $DB_NAME ready."