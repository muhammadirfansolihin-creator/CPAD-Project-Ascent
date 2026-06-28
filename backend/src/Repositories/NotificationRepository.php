<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class NotificationRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getByUserId(int $userId, int $limit = 50): array {
        $stmt = $this->db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function verifyOwnership(int $id, int $userId): bool {
        $stmt = $this->db->prepare('SELECT id FROM notifications WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return (bool)$stmt->fetch();
    }

    public function markAsRead(int $id): void {
        $stmt = $this->db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        $stmt->execute([$id]);
    }
}