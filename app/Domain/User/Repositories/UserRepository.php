<?php

namespace App\Domain\User\Repositories;

use App\Database\Db;
use App\Domain\User\Models\User;
use PDO;

class UserRepository
{
    private PDO $dbRead;
    private PDO $dbWrite;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->dbWrite = Db::getInstance(Db::QUERY_TYPE_WRITE);
        $this->dbRead = Db::getInstance();
    }

    public function getFirstUser(): ?User
    {
        $sql = <<<SQL
            SELECT * FROM users LIMIT 1;
        SQL;

        $stmt = $this->dbRead->prepare($sql);
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
    public function getUserById(int $id): ?User
    {
        $sql = <<<SQL
            SELECT * 
            FROM users 
            WHERE id = :id
            LIMIT 1;
        SQL;

        $stmt = $this->dbRead->prepare($sql);
        $stmt->bindParam(':id', $id);
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


    public function batchInsert(array $values): void
    {
        $sql = "INSERT INTO users (UUID, first_name, second_name, password, birthdate, city) VALUES " . implode(',', $values);

        $stmt = $this->dbWrite->prepare($sql);

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

        $stmt = $this->dbRead->prepare($sql);
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

    public function getAllUsers(int $batchCount = 1000): \Generator
    {
        $sql = <<<SQL
            SELECT * 
            FROM users 
            ORDER BY id
            LIMIT :limit
            OFFSET :offset;
        SQL;

        $stmt = $this->dbRead->prepare($sql);
        $stmt->bindParam(':limit', $batchCount, PDO::PARAM_INT);

        $offset = 0;

        do {
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                break;
            }

            foreach ($rows as $data) {
                yield new User(
                    $data['id'],
                    $data['uuid'],
                    $data['first_name'],
                    $data['second_name'],
                    $data['birthdate'],
                    $data['biography'] ?? '',
                    $data['city'],
                    $data['password']
                );
            }

            $offset += $batchCount;

        } while (count($rows) === $batchCount);
    }

    public function addFriend(int $userId, int $friendUserId): void
    {
        $sql = <<<SQL
            INSERT INTO user_friends (user_id, friend_user_id) 
            VALUES (:user_id, :friend_user_id)
        SQL;

        $stmt = $this->dbWrite->prepare($sql);

        $stmt->execute([
            ':user_id' => $userId,
            ':friend_user_id' => $friendUserId,
        ]);
    }

    public function addFriends(int $userId, array $friendIds): void
    {

        if (empty($friendIds)) {
            return;
        }

        // Убираем дубликаты и пустые значения
        $friendIds = array_unique(array_filter($friendIds));

        // Подготавливаем множественную вставку
        $placeholders = [];
        $params = [];

        foreach ($friendIds as $index => $friendId) {
            $placeholders[] = "(:user_id, :friend_user_id_{$index})";
            $params[":friend_user_id_{$index}"] = $friendId;
        }

        // Добавляем userId один раз для всех плейсхолдеров
        $params[':user_id'] = $userId;

        $sql = sprintf(
            "INSERT INTO user_friends (user_id, friend_user_id) VALUES %s",
            implode(', ', $placeholders)
        );

        $stmt = $this->dbWrite->prepare($sql);
        $stmt->execute($params);
    }

    public function createUser(array $data): void
    {
        $sql = <<<SQL
            INSERT INTO users (UUID, first_name, second_name, password, birthdate, city, biography) 
            VALUES (:uuid, :first_name, :second_name, :password, :birthdate, :city, :biography)
        SQL;


        $stmt = $this->dbWrite->prepare($sql);

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

        $stmt = $this->dbRead->prepare($sql);
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

    public function getFriendIds(int $userId): array
    {
        $sql = <<<SQL
            SELECT friend_user_id
            FROM user_friends
            WHERE user_id = :user_id
        SQL;

        $stmt = $this->dbRead->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        return $rows ?: [];
    }
}