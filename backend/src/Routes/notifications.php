<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

global $app;

// ── GET /api/notifications ────────────────────────────────────────────────────
$app->get('/api/notifications', function (Request $req, Response $res) {
    $db     = getDB();
    $userId = (int) $req->getAttribute('userId');

    $stmt = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$userId]);
    $rows = $stmt->fetchAll();

    return jsonResponse($res, array_map(fn($n) => [
        'id'        => (int) $n['id'],
        'orderId'   => (int) $n['order_id'],
        'message'   => $n['message'],
        'isRead'    => (bool) $n['is_read'],
        'createdAt' => $n['created_at'],
    ], $rows));
})->add(new AuthMiddleware());

// ── PATCH /api/notifications/{id}/read ────────────────────────────────────────
$app->patch('/api/notifications/{id}/read', function (Request $req, Response $res, array $args) {
    $db     = getDB();
    $id     = (int) $args['id'];
    $userId = (int) $req->getAttribute('userId');

    // Verify ownership — only the notification's owner can mark it read
    $own = $db->prepare('SELECT id FROM notifications WHERE id = ? AND user_id = ?');
    $own->execute([$id, $userId]);
    if (!$own->fetch()) return jsonResponse($res, ['error' => 'Notification not found or access denied'], 403);

    $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$id]);

    return jsonResponse($res, ['id' => $id, 'isRead' => true]);
})->add(new AuthMiddleware());