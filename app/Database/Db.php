<?php

namespace App\Database;

use PDO;
use PDOException;
use App\Config;

class Db
{
    public const string QUERY_TYPE_READ = 'read';
    public const string QUERY_TYPE_WRITE = 'write';

    private static ?PDO $instance = null;

    /**
     * @throws \Exception
     */
    public static function getInstance(string $queryType = self::QUERY_TYPE_READ): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection($queryType);
        }

        return self::$instance;
    }

    private static function createConnection(string $queryType): PDO
    {
//        $dsn = sprintf(
//            '%s:host=%s;port=%s;dbname=%s;charset=%s',
//            Config::get('DB_DRIVER'),
//            Config::get('DB_HOST'),
//            Config::get('DB_PORT'),
//            Config::get('DB_NAME'),
//            Config::get('DB_CHARSET'),
//        );

        $configsDatabase = Config::get('database');

        if ($queryType === self::QUERY_TYPE_WRITE) {
            $configs = $configsDatabase['write'];
        } else {
            $random = random_int(0, 1);
            $configs = $configsDatabase['read'][$random];
        }

//        $configs = $configsDatabase['common'];

        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s',
            $configs['DB_DRIVER'],
            $configs['DB_HOST'],
            $configs['DB_PORT'],
            $configs['DB_NAME'],
        );

        try {
            $pdo = new PDO(
                $dsn,
                $configs['DB_USER'],
                $configs['DB_PASSWORD'],
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