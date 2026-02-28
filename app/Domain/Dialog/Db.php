<?php

namespace App\Domain\Dialog;

use PDO;
use PDOException;

class Db
{
    private static ?PDO $connection = null;

    private string $host;
    private int $port;
    private string $database;
    private string $username;
    private string $password;

    public function __construct()
    {
        $this->host = 'citus_master';
        $this->port = 5432;
        $this->database = 'postgres';
        $this->username = 'postgres';
        $this->password = 'admin';
    }

    /**
     * Получить соединение с БД (синглтон)
     */
    public function getConnection(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;options=\'--client_encoding=UTF8\'',
                $this->host,
                $this->port,
                $this->database
            );

            try {
                self::$connection = new PDO(
                    $dsn,
                    $this->username,
                    $this->password,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::ATTR_PERSISTENT => true
                    ]
                );

                // Проверяем расширение Citus
                $this->checkCitusExtension();

            } catch (PDOException $e) {
                throw new PDOException(
                    "Database connection failed: " . $e->getMessage(),
                    (int)$e->getCode(),
                    $e
                );
            }
        }

        return self::$connection;
    }

    /**
     * Проверить наличие расширения Citus
     */
    private function checkCitusExtension(): void
    {
        try {
            $stmt = self::$connection->query("SELECT extname FROM pg_extension WHERE extname = 'citus'");
            $extension = $stmt->fetch();

            if (!$extension) {
                throw new \RuntimeException("Citus extension is not installed in the database");
            }

        } catch (PDOException $e) {
            // Возможно таблица pg_extension недоступна, пробуем другой способ
            try {
                self::$connection->query("SHOW citus.version")->fetch();
            } catch (PDOException $ex) {
                throw new \RuntimeException("Citus extension check failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Выполнить запрос с параметрами
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->getConnection()->prepare($sql);
        $stmt->execute($params);

        // Для SELECT запросов возвращаем данные
        if (stripos($sql, 'SELECT') === 0) {
            return $stmt->fetchAll();
        }

        // Для INSERT RETURNING возвращаем одну строку
        if (stripos($sql, 'INSERT') === 0 && stripos($sql, 'RETURNING') !== false) {
            return $stmt->fetch() ?: [];
        }

        return [];
    }
}