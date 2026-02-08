<?php

namespace App\Database;

use PDO;

class Migration
{
    private PDO $pdo;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->pdo = Db::getInstance();
    }

    public function run(): void
    {
        echo "Starting migrations...\n";

        $this->createUsersTable();
        $this->createTokensTable();

        echo "All migrations completed successfully.\n";
    }

    public function dropAll(): void
    {
        $tables = ['posts', 'tokens', 'users'];

        foreach ($tables as $table) {
            try {
                $this->pdo->exec("DROP TABLE IF EXISTS $table");
                echo "Table '$table' dropped.\n";
            } catch (\Exception $e) {
                echo "Error dropping table '$table': " . $e->getMessage() . "\n";
            }
        }
    }

    public function createUsersTable(): void
    {
//        $sql = <<<SQL
//            CREATE TABLE IF NOT EXISTS `users` (
//                id INT AUTO_INCREMENT PRIMARY KEY,
//                uuid VARCHAR(255) NOT NULL,
//                first_name VARCHAR(255) NOT NULL,
//                second_name VARCHAR(255) NOT NULL,
//                password TEXT NOT NULL,
//                birthdate DATE NOT NULL,
//                city VARCHAR(255) NOT NULL,
//                biography TEXT DEFAULT NULL
//            )
//        SQL;

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                uuid VARCHAR(255) NOT NULL,
                first_name VARCHAR(255) NOT NULL,
                second_name VARCHAR(255) NOT NULL,
                password TEXT NOT NULL,
                birthdate DATE NOT NULL,
                city VARCHAR(255) NOT NULL,
                biography TEXT DEFAULT NULL
            )
        SQL;

        $this->pdo->exec($sql);
        echo "Table 'users' created successfully.\n";
    }

    public function createTokensTable(): void
    {
//        $sql = <<<SQL
//            CREATE TABLE IF NOT EXISTS `tokens` (
//                id INT AUTO_INCREMENT PRIMARY KEY,
//                user_id INT NOT NULL,
//                token TEXT NOT NULL,
//                created_at TIMESTAMP NOT NULL,
//                expired_at TIMESTAMP NOT NULL
//            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
//        SQL;

        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS tokens (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                token TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP NOT NULL,
                expired_at TIMESTAMP NOT NULL
            )
        SQL;

        $this->pdo->exec($sql);
        echo "Table 'tokens' created successfully.\n";
    }
}