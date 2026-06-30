<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

class VendorRepository {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getAllActive(string $search = ''): array {
        $sql = 'SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.owner_id WHERE v.status = \'active\'';
        $args = [];
        if ($search !== '') {
            $sql .= ' AND (v.name LIKE ? OR v.location LIKE ?)';
            $args = ["%$search%", "%$search%"];
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($args);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare('SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.owner_id WHERE v.id = ?');
        $stmt->execute([$id]);
        $vendor = $stmt->fetch();
        return $vendor ? $vendor : null;
    }

    public function getAverageRating(int $vendorId): ?float {
        $stmt = $this->db->prepare('SELECT AVG(rating) AS avg FROM reviews WHERE vendor_id = ?');
        $stmt->execute([$vendorId]);
        $row = $stmt->fetch();
        return $row['avg'] !== null ? (float)$row['avg'] : null;
    }

    public function getOrderCount(int $vendorId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) AS cnt FROM orders WHERE vendor_id = ?');
        $stmt->execute([$vendorId]);
        return (int)$stmt->fetch()['cnt'];
    }

    public function create(int $ownerId, string $name, string $location, string $openingHours, ?string $imageUrl): int {
        $stmt = $this->db->prepare('INSERT INTO vendors (owner_id, name, location, opening_hours, image_url, status) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$ownerId, $name, $location, $openingHours, $imageUrl, 'pending']);
        return (int)$this->db->lastInsertId();
    }

    public function getMenuItems(int $vendorId): array {
        $stmt = $this->db->prepare('SELECT * FROM menu_items WHERE vendor_id = ? ORDER BY category, name');
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll();
    }

    public function searchMenuItems(string $search): array {
    $stmt = $this->db->prepare(
        'SELECT mi.*, v.name AS vendor_name, v.id AS vendor_id
         FROM menu_items mi
         JOIN vendors v ON v.id = mi.vendor_id
         WHERE v.status = "active" AND mi.in_stock = 1
           AND (mi.name LIKE ? OR mi.description LIKE ?)
         LIMIT 20'
    );
    $stmt->execute(["%$search%", "%$search%"]);
    return $stmt->fetchAll();
}

    public function findVendorByOwnerId(int $ownerId): ?array {
        $stmt = $this->db->prepare('SELECT * FROM vendors WHERE owner_id = ?');
        $stmt->execute([$ownerId]);
        $vendor = $stmt->fetch();
        return $vendor ? $vendor : null;
    }

    public function updateStatus(int $vendorId, string $status): void {
        $stmt = $this->db->prepare('UPDATE vendors SET status = ? WHERE id = ?');
        $stmt->execute([$status, $vendorId]);
    }

    public function getAllForAdmin(): array {
        $stmt = $this->db->prepare('SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.owner_id ORDER BY v.created_at DESC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countVendors(): int {
        return (int)$this->db
            ->query("SELECT COUNT(*) FROM vendors")
            ->fetchColumn();
    }

    public function countActiveVendors(): int{
        return (int)$this->db
            ->query("SELECT COUNT(*) FROM vendors WHERE status='active'")
            ->fetchColumn();
    }

    public function countOrders(): int{
        return (int)$this->db
            ->query("SELECT COUNT(*) FROM orders")
            ->fetchColumn();
    }

    public function getTotalRevenue(): float{
        return (float)$this->db
            ->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='collected'")
            ->fetchColumn();
    }

    public function addMenuItem(int $vendorId,string $name,string $description,float $price,string $category,bool $inStock): int {

        $stmt = $this->db->prepare("
            INSERT INTO menu_items
            (vendor_id,name,description,price,category,in_stock)
            VALUES (?,?,?,?,?,?)
        ");

        $stmt->execute([
            $vendorId,
            $name,
            $description,
            $price,
            $category,
            $inStock ? 1 : 0
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findMenuItem(int $id): ?array{
        $stmt = $this->db->prepare(
            "SELECT * FROM menu_items WHERE id=?"
        );

        $stmt->execute([$id]);

        $item = $stmt->fetch();

        return $item ?: null;
    }

    public function updateMenuItem(int $id,string $name,string $description,float $price,string $category,bool $inStock): void {

        $stmt = $this->db->prepare("
            UPDATE menu_items
            SET
                name=?,
                description=?,
                price=?,
                category=?,
                in_stock=?
            WHERE id=?
        ");

        $stmt->execute([
            $name,
            $description,
            $price,
            $category,
            $inStock ? 1 : 0,
            $id
        ]);
    }

    public function deleteMenuItem(int $id): void{
        $stmt = $this->db->prepare(
            "DELETE FROM menu_items WHERE id=?"
        );

        $stmt->execute([$id]);
    }

    public function toggleStock(int $id): array{
        $item = $this->findMenuItem($id);

        $newStock = !$item['in_stock'];

        $stmt = $this->db->prepare(
            "UPDATE menu_items SET in_stock=? WHERE id=?"
        );

        $stmt->execute([
            $newStock ? 1 : 0,
            $id
        ]);

        return $this->findMenuItem($id);
    }

    public function toggleOpen(int $vendorId): bool{
        $stmt = $this->db->prepare(
            "UPDATE vendors
            SET is_open = NOT is_open
            WHERE id=?"
        );

        $stmt->execute([$vendorId]);
        error_log("Rows updated: " . $stmt->rowCount());
        $stmt = $this->db->prepare(
            "SELECT is_open FROM vendors WHERE id=?"
        );

        $stmt->execute([$vendorId]);

        return (bool)$stmt->fetchColumn();
    }

    public function updateProfile(
        int $vendorId,
        string $name,
        string $location,
        string $openingHours
    ): void {

        $stmt = $this->db->prepare("
            UPDATE vendors
            SET
                name = ?,
                location = ?,
                opening_hours = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $name,
            $location,
            $openingHours,
            $vendorId
        ]);
    }
}