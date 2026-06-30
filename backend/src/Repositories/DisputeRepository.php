<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class DisputeRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAllWithReporters(): array {
        $stmt = $this->db->prepare('SELECT d.*, u.name AS reporter_name FROM disputes d JOIN users u ON u.id = d.reported_by ORDER BY d.created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByIdWithReporter(int $id): ?array {
        $stmt = $this->db->prepare('SELECT d.*, u.name AS reporter_name FROM disputes d JOIN users u ON u.id = d.reported_by WHERE d.id = ?');
        $stmt->execute([$id]);
        $dispute = $stmt->fetch();
        return $dispute ? $dispute : null;
    }

    public function resolve(int $id, string $resolution): void {
        $stmt = $this->db->prepare('UPDATE disputes SET status = \'resolved\', resolution = ? WHERE id = ?');
        $stmt->execute([$resolution, $id]);
    }

    public function create(int $userId, int $orderId, string $description): void {
        $stmt = $this->db->prepare('INSERT INTO disputes (reported_by, order_id, description, status, created_at) VALUES (?, ?, ?, \'open\', ?)');
        $stmt->execute([$userId, $orderId, $description, date('Y-m-d H:i:s')]);
    }

    public function hasDisputeForOrder(int $orderId): bool {
        $stmt = $this->db->prepare('SELECT id FROM disputes WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return (bool) $stmt->fetch();
    }

    public function countPendingDisputes(): int{
        return (int)$this->db
            ->query("SELECT COUNT(*) FROM disputes WHERE status='open'")
            ->fetchColumn();
    }

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM disputes
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll();
    }
}