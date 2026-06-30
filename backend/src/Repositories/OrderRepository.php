<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Exception;

class OrderRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getStudentOrders(int $userId): array {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getAllOrders(): array
    {
        $stmt = $this->db->query("
            SELECT *
            FROM orders
            ORDER BY created_at DESC
        ");

        return $stmt->fetchAll();
    }

    public function getOrderItems(int $orderId): array {
        $stmt = $this->db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }

    public function getVendorName(int $vendorId): string {
        $stmt = $this->db->prepare('SELECT name FROM vendors WHERE id = ?');
        $stmt->execute([$vendorId]);
        return $stmt->fetch()['name'] ?? 'Unknown Stall';
    }

    public function getUserName(int $userId): string {
        $stmt = $this->db->prepare('SELECT name FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch()['name'] ?? 'Unknown User';
    }

    public function findMenuItem(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        return $item ? $item : null;
    }

    public function createOrder(int $userId, int $vendorId, string $pickupAt, float $total, array $items): int {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('INSERT INTO orders (user_id, vendor_id, status, total, pickup_at, created_at) VALUES (?, ?, \'placed\', ?, ?, ?)');
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$userId, $vendorId, $total, $pickupAt, $now]);
            $orderId = (int)$this->db->lastInsertId();

            $insItem = $this->db->prepare('INSERT INTO order_items (order_id, menu_item_id, name, qty, unit_price) VALUES (?, ?, ?, ?, ?)');
            foreach ($items as $item) {
                $insItem->execute([$orderId, $item['menuItemId'], $item['name'], $item['qty'], $item['unitPrice']]);
            }

            // Create system notification for vendor
            $vStmt = $this->db->prepare('SELECT owner_id FROM vendors WHERE id = ?');
            $vStmt->execute([$vendorId]);
            $vOwner = $vStmt->fetch();
            if ($vOwner) {
                $notif = $this->db->prepare('INSERT INTO notifications (user_id, order_id, message, created_at) VALUES (?, ?, ?, ?)');
                $notif->execute([$vOwner['owner_id'], $orderId, "New order #{$orderId} placed!", $now]);
            }

            $this->db->commit();
            return $orderId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getVendorOrders(int $vendorId): array {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE vendor_id = ? ORDER BY created_at DESC');
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        return $order ? $order : null;
    }

    public function updateStatus(int $id, string $status): void {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('UPDATE orders SET status = ? WHERE id = ?');
            $stmt->execute([$status, $id]);

            // Notify Student
            $stmtOrder = $this->db->prepare('SELECT user_id FROM orders WHERE id = ?');
            $stmtOrder->execute([$id]);
            $order = $stmtOrder->fetch();
            if ($order) {
                $notif = $this->db->prepare('INSERT INTO notifications (user_id, order_id, message, created_at) VALUES (?, ?, ?, ?)');
                $notif->execute([$order['user_id'], $id, "Your order #{$id} is now updated to: {$status}.", date('Y-m-d H:i:s')]);
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function createReview(int $userId, int $vendorId, int $orderId, int $rating, string $comment): void {
        $stmt = $this->db->prepare('INSERT INTO reviews (user_id, vendor_id, order_id, rating, comment, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $vendorId, $orderId, $rating, $comment, date('Y-m-d H:i:s')]);
    }

    public function hasReviewForOrder(int $userId, int $orderId): bool {
        $stmt = $this->db->prepare('SELECT id FROM reviews WHERE user_id = ? AND order_id = ?');
        $stmt->execute([$userId, $orderId]);
        return (bool) $stmt->fetch();
    }

    public function getStudentReviews(int $userId): array {
        $stmt = $this->db->prepare(
            'SELECT r.id, r.rating, r.comment, r.created_at,
                    v.id AS vendor_id, v.name AS vendor_name,
                    (SELECT GROUP_CONCAT(oi.name SEPARATOR ", ")
                     FROM orders o
                     JOIN order_items oi ON oi.order_id = o.id
                     WHERE o.user_id = r.user_id AND o.vendor_id = r.vendor_id
                     ORDER BY o.created_at DESC LIMIT 1
                    ) AS items_ordered
             FROM reviews r
             JOIN vendors v ON v.id = r.vendor_id
             WHERE r.user_id = ?
             ORDER BY r.created_at DESC'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getVendorReviews(int $vendorId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                r.id,
                r.user_id,
                r.rating,
                r.comment,
                r.created_at,
                u.name AS user_name
            FROM reviews r
            JOIN users u ON u.id = r.user_id
            WHERE r.vendor_id = ?
            ORDER BY r.created_at DESC
        ");

        $stmt->execute([$vendorId]);

        return $stmt->fetchAll();
    }

    public function getActiveBanner(): ?array
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM dynamic_banners
            WHERE is_active = 1
             AND (
             ( start_time <= end_time AND CURTIME() BETWEEN start_time AND end_time)
             OR ( start_time > end_time AND ( CURTIME() >= start_time OR CURTIME() <= end_time)
             )) 
            LIMIT 1
        ");

        $stmt->execute();

        $banner = $stmt->fetch();

        return $banner ?: null;
    }
}