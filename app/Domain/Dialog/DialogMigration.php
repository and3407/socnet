<?php

namespace App\Domain\Dialog;

use PDOException;

class DialogMigration
{
    private Db $db;

    public function __construct()
    {
        $this->db = new Db();
    }

    public function run(): void
    {
        $this->createDialogTable();
        echo 'createDialogTable' . PHP_EOL;

        $this->createDialogMessageTable();
        echo 'createDialogMessageTable' . PHP_EOL;

        $this->createDialogUserTable();
        echo 'createDialogUserTable' . PHP_EOL;

        $this->createShard();
        echo 'createShard' . PHP_EOL;
    }

    public function createShard(): void
    {
        $pdo = $this->db->getConnection();

        $pdo->exec("SELECT create_distributed_table('dialogs', 'id')");
        $pdo->exec("SELECT create_distributed_table('dialog_messages', 'dialog_id')");
        $pdo->exec("SELECT create_distributed_table('dialog_users', 'dialog_id')");
    }

    public function createDialogTable(): void
    {
        $pdo = $this->db->getConnection();

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS dialogs (
                id BIGSERIAL PRIMARY KEY,
                name VARCHAR(255) DEFAULT NULL,
                creater_user_id BIGINT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP DEFAULT NULL
            )
        SQL;

        $pdo->exec($sql);
    }

    public function createDialogMessageTable(): void
    {
        $pdo = $this->db->getConnection();

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS dialog_messages (
                id BIGSERIAL,
                dialog_id BIGINT,
                author_user_id BIGINT,
                content TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP DEFAULT NULL,
                PRIMARY KEY (dialog_id, id)
            );
        SQL;

        $pdo->exec($sql);
    }

    public function createDialogUserTable(): void
    {
        $pdo = $this->db->getConnection();

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS dialog_users (
                dialog_id BIGINT,
                user_id BIGINT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP DEFAULT NULL,
                PRIMARY KEY (dialog_id, user_id)
            )
        SQL;

        $pdo->exec($sql);
    }
}