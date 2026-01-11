<?php

namespace App\Domain\Token\Repositories;

use App\Database\Db;
use App\Domain\Token\Models\Token;
use PDO;

class TokenRepository
{
    private PDO $pdo;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $this->pdo = Db::getInstance();
    }

    public function getTokenByToken(string $token): ?Token
    {
        $sql = <<<SQL
            SELECT * 
            FROM tokens 
            WHERE token = :token
            LIMIT 1;
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Token(
            $data['id'],
            $data['user_id'],
            $data['token'],
            $data['created_at'],
            $data['expired_at'],
        );
    }

    public function getTokenByUserId(int $userId): ?Token
    {
        $sql = <<<SQL
            SELECT * 
            FROM tokens 
            WHERE user_id = :user_id
            LIMIT 1;
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new Token(
            $data['id'],
            $data['user_id'],
            $data['token'],
            $data['created_at'],
            $data['expired_at'],
        );
    }

    public function deleteTokenById(string $tokenId): void
    {
        $sql = <<<SQL
            DELETE FROM tokens 
            WHERE id = :id
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':id', $tokenId);
        $stmt->execute();
    }

    public function createToken(int $userId, string $token, string $createdAt, $expiredAt): void
    {
        $sql = <<<SQL
            INSERT INTO tokens (user_id, token, created_at, expired_at) 
            VALUES (:user_id, :token, :created_at, :expired_at)
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->bindParam(':expired_at', $expiredAt);

        $stmt->execute();
    }
}