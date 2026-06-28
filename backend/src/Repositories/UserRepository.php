<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class UserRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ? $user : null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int {
        $stmt = $this->db->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $email, $passwordHash, $role, date('Y-m-d H:i:s')]);
        return (int)$this->db->lastInsertId();
    }

    public function getStats(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) AS order_count,
                    COALESCE(SUM(CASE WHEN status = "collected" THEN total ELSE 0 END), 0) AS total_spent
             FROM orders WHERE user_id = :user_id'
        );
        $stmt->execute([':user_id' => $userId]);
        $row = $stmt->fetch();
        $spent = (float)$row['total_spent'];

        return [
            'orderCount' => (int)$row['order_count'],
            'totalSpent' => $spent,
            'points'     => (int)floor($spent),
        ];
    }
}