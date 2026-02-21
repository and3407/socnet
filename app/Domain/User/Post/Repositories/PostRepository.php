<?php

namespace App\Domain\User\Post\Repositories;

use App\Database\Db;
use PDO;

class PostRepository
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

    public function createPost(array $data): void
    {
        $sql = <<<SQL
            INSERT INTO posts (text, author_user_id, created_at) 
            VALUES (:text, :author_user_id, :created_at)
        SQL;


        $stmt = $this->dbWrite->prepare($sql);

        $stmt->execute([
            ':text' => $data['text'],
            ':author_user_id' => $data['author_user_id'],
            ':created_at' => $data['created_at'],
        ]);
    }

    public function getRandomAuthorUserId(int $countId = 100): array
    {
        $sql = <<<SQL
            SELECT author_user_id
            FROM posts
            ORDER BY random()
            LIMIT :countId;
        SQL;

        $stmt = $this->dbWrite->prepare($sql);

        $stmt->execute([
            ':countId' => $countId,
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_column($rows, 'author_user_id');
    }
}