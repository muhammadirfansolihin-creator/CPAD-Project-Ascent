public function getStats(int $userId): array {
    $stmt = $this->pdo->prepare(
        'SELECT
            COUNT(*) AS order_count,
            COALESCE(SUM(CASE WHEN status = "collected" THEN total ELSE 0 END), 0) AS total_spent
         FROM orders
         WHERE user_id = :user_id'
    );
    $stmt->execute([':user_id' => $userId]);
    $row = $stmt->fetch();

    $spent = (float)$row['total_spent'];

    return [
        'orderCount' => (int)$row['order_count'],
        'totalSpent' => $spent,
        'points'     => (int)floor($spent), // RM1 = 1 point
    ];
}