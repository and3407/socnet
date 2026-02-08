<?php

namespace App\Database;

use PDO;
use PDOException;
use App\Config;

class Db
{
    private static ?PDO $instance = null;

    /**
     * @throws \Exception
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    private static function createConnection(): PDO
    {
//        $dsn = sprintf(
//            '%s:host=%s;port=%s;dbname=%s;charset=%s',
//            Config::get('DB_DRIVER'),
//            Config::get('DB_HOST'),
//            Config::get('DB_PORT'),
//            Config::get('DB_NAME'),
//            Config::get('DB_CHARSET'),
//        );

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            Config::get('DB_DRIVER'),
            Config::get('DB_HOST'),
            Config::get('DB_PORT'),
            Config::get('DB_NAME'),
        );

        try {
            $pdo = new PDO(
                $dsn,
                Config::get('DB_USER'),
                Config::get('DB_PASSWORD'),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_STRINGIFY_FETCHES => false
                ]
            );

            // Устанавливаем timezone
//            $pdo->exec("SET time_zone = '+03:00'");

            return $pdo;
        } catch (PDOException $e) {
            throw new \Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public static function disconnect(): void
    {
        self::$instance = null;
    }

    public static function isConnected(): bool
    {
        try {
            self::getInstance()->query('SELECT 1');
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}