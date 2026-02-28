<?php

namespace App\Domain\Dialog\Repositories;

use App\Domain\Dialog\Db;
use PDO;

class DialogRepository
{
    private Db $db;

    public function __construct()
    {
        $this->db = new Db();
    }

    public function getDialogMessages(int $dialogId): array
    {
        $sql = <<<SQL
            SELECT *
            FROM dialog_messages
            WHERE dialog_id = :dialogId
            ORDER BY id DESC
        SQL;

        $pdo = $this->db->getConnection();

        $params = [
            ':dialogId' => $dialogId,
        ];

        $stmt = $pdo->prepare($sql);

        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createMessage(array $data): ?int
    {
        $pdo = $this->db->getConnection();

        $sql = <<<SQL
            INSERT INTO dialog_messages (dialog_id, author_user_id, content) 
            VALUES (:dialog_id, :author_user_id, :content)
            RETURNING id
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':dialog_id' => $data['dialog_id'],
            ':author_user_id' => $data['author_user_id'],
            ':content' => $data['content'],
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return  $result ? (int) $result['id'] : null;
    }

    public function getDialogData(int $authorUserId, int $recipientUserId): array
    {
        $sql = <<<SQL
            SELECT d3.*
            FROM dialog_users d1
            INNER JOIN dialog_users d2 ON d1.dialog_id = d2.dialog_id
            INNER JOIN dialogs d3 ON d2.dialog_id = d3.id
            WHERE d1.user_id = :authorUserId
              AND d2.user_id = :recipientUserId
              AND d1.deleted_at IS NULL
              AND d2.deleted_at IS NULL
            LIMIT 1
        SQL;

        $pdo = $this->db->getConnection();

        $params = [
            ':authorUserId' => $authorUserId,
            ':recipientUserId' => $recipientUserId,
        ];

        $stmt = $pdo->prepare($sql);

        $stmt->execute($params);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $rows[0] ?? [];
    }

    public function createDialog(array $data): ?int
    {
        $pdo = $this->db->getConnection();

        $sql = <<<SQL
            INSERT INTO dialogs (name, creater_user_id) 
            VALUES (:name, :creater_user_id)
            RETURNING id
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':name' => $data['name'] ?? null,
            ':creater_user_id' => $data['creater_user_id'],
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $dialogId =  $result ? (int) $result['id'] : null;

        $sql = <<<SQL
            INSERT INTO dialog_users (dialog_id, user_id) 
            VALUES (:dialog_id, :user_id)
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':dialog_id' => $dialogId,
            ':user_id' => $data['creater_user_id'],
        ]);

        $sql = <<<SQL
            INSERT INTO dialog_users (dialog_id, user_id) 
            VALUES (:dialog_id, :user_id)
        SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':dialog_id' => $dialogId,
            ':user_id' => $data['recipientUserId'],
        ]);

        return $dialogId;
    }
}