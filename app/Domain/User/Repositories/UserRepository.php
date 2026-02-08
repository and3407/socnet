<?php

namespace App\Domain\User\Repositories;

use App\Database\Db;
use App\Domain\User\Models\User;
use PDO;

class UserRepository
{
    private PDO $pdo;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->pdo = Db::getInstance();
    }

    public function batchInsert(array $values): void
    {
        $sql = "INSERT INTO users (UUID, first_name, second_name, password, birthdate, city) VALUES " . implode(',', $values);

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute();
    }

    public function getUserByUuid(string $uuid): ?User
    {
        $sql = <<<SQL
            SELECT * 
            FROM users 
            WHERE uuid = :uuid
            LIMIT 1;
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':uuid', $uuid);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new User(
            $data['id'],
            $data['uuid'],
            $data['first_name'],
            $data['second_name'],
            $data['birthdate'],
            $data['biography'],
            $data['city'],
            $data['password'],
        );
    }

    public function createUser(array $data): void
    {
        $sql = <<<SQL
            INSERT INTO users (UUID, first_name, second_name, password, birthdate, city, biography) 
            VALUES (:uuid, :first_name, :second_name, :password, :birthdate, :city, :biography)
        SQL;


        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            ':uuid' => $data['uuid'],
            ':first_name' => $data['first_name'],
            ':second_name' => $data['second_name'],
            ':password' => $data['password'],
            ':birthdate' => $data['birthdate'],
            ':city' => $data['city'],
            ':biography' => $data['biography'] ?? null,
        ]);
    }

    public function searchByName(string $firstName, string $lastName): array
    {
        $sql = <<<SQL
            SELECT *
            FROM users
            WHERE first_name LIKE :first_name
            AND second_name LIKE :second_name
        SQL;

        $stmt = $this->pdo->prepare($sql);
//
        $stmt->execute([
            ':first_name' => '%' . $firstName . '%',
            ':second_name' => '%' . $lastName . '%',
        ]);

//        $stmt->execute([
//            ':first_name' => $firstName . '%',
//            ':second_name' => $lastName . '%',
//        ]);

        // -------------------------------
//        $sql2 = <<<SQL
//            SELECT * FROM users_3
//            WHERE MATCH(first_name, second_name)
//            AGAINST(? IN BOOLEAN MODE);
//        SQL;
//
//         //Формируем поисковую строку
//        $searchTerm = '';
//        if (!empty($firstName) && !empty($lastName)) {
//            // +Иван* +Петров* - оба должны присутствовать
//            $searchTerm = '+' . $firstName . '* +' . $lastName . '*';
//        } elseif (!empty($firstName)) {
//            $searchTerm = $firstName . '*';
//        } elseif (!empty($lastName)) {
//            $searchTerm = $lastName . '*';
//        }
//
//        $stmt = $this->pdo->prepare($sql2);
//        $stmt->execute([$searchTerm]);
        // -------------------------------

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];

        foreach ($rows as $data) {
            $result[] = new User(
                $data['id'],
                $data['uuid'],
                $data['first_name'],
                $data['second_name'],
                $data['birthdate'],
                $data['biography'] ?? '',
                $data['city'],
                $data['password'],
            );
        }

        return $result;
    }
}