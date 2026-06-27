<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

global $app;
$adminOnly = new AuthMiddleware('admin');

// ── GET /api/admin/vendors ────────────────────────────────────────────────────
$app->get('/api/admin/vendors', function (Request $req, Response $res) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.owner_id ORDER BY v.created_at DESC');
    $stmt->execute([]);
    $vendors = $stmt->fetchAll();
    $result  = [];
    foreach ($vendors as $v) {
        $r = $db->prepare('SELECT AVG(rating) AS avg FROM reviews WHERE vendor_id=?'); $r->execute([(int)$v['id']]);
        // FIX: call fetch() once, store it, then use the stored value
        $rRow = $r->fetch();
        $rating = ($rRow && $rRow['avg'] !== null) ? round((float)$rRow['avg'], 1) : null;

        $c = $db->prepare('SELECT COUNT(*) AS cnt FROM orders WHERE vendor_id=?'); $c->execute([(int)$v['id']]);
        $result[] = [
            'id'           => (int)$v['id'],
            'ownerId'      => (int)$v['owner_id'],
            'ownerName'    => $v['owner_name'],
            'name'         => $v['name'],
            'location'     => $v['location'],
            'openingHours' => $v['opening_hours'],
            'imageUrl'     => $v['image_url'],
            'isActive'     => (bool)$v['is_active'],
            'isOpen'       => (bool)$v['is_open'],
            'status'       => $v['status'],
            'rating'       => $rating,
            'totalOrders'  => (int)$c->fetch()['cnt'],
            'createdAt'    => $v['created_at'],
        ];
    }
    return jsonResponse($res, $result);
})->add($adminOnly);

// ── PATCH /api/vendors/{id}/status ───────────────────────────────────────────
$app->patch('/api/vendors/{id}/status', function (Request $req, Response $res, array $args) {
    $body   = (array) $req->getParsedBody();
    $status = $body['status'] ?? '';
    if (!in_array($status, ['pending', 'active', 'inactive'])) return jsonResponse($res, ['error' => 'Invalid status'], 400);

    $db       = getDB();
    $id       = (int) $args['id'];
    $isActive = $status === 'active' ? 1 : 0;
    $db->prepare('UPDATE vendors SET status=?, is_active=? WHERE id=?')->execute([$status, $isActive, $id]);
    $stmt = $db->prepare('SELECT * FROM vendors WHERE id=?'); $stmt->execute([$id]);
    $v    = $stmt->fetch();
    if (!$v) return jsonResponse($res, ['error' => 'Vendor not found'], 404);
    return jsonResponse($res, [
        'id'           => (int)$v['id'],
        'ownerId'      => (int)$v['owner_id'],
        'ownerName'    => null,
        'name'         => $v['name'],
        'location'     => $v['location'],
        'openingHours' => $v['opening_hours'],
        'imageUrl'     => $v['image_url'],
        'isActive'     => (bool)$v['is_active'],
        'isOpen'       => (bool)$v['is_open'],
        'status'       => $v['status'],
        'rating'       => null,
        'totalOrders'  => 0,
        'createdAt'    => $v['created_at'],
    ]);
})->add($adminOnly);

// ── GET /api/admin/users ──────────────────────────────────────────────────────
$app->get('/api/admin/users', function (Request $req, Response $res) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC');
    $stmt->execute([]);
    return jsonResponse($res, array_map(fn($u) => [
        'id'        => (int)$u['id'],
        'name'      => $u['name'],
        'email'     => $u['email'],
        'role'      => $u['role'],
        'createdAt' => $u['created_at'],
    ], $stmt->fetchAll()));
})->add($adminOnly);

// ── GET /api/admin/analytics ──────────────────────────────────────────────────
$app->get('/api/admin/analytics', function (Request $req, Response $res) {
    $db     = getDB();
    $period = $req->getQueryParams()['period'] ?? null;

    $sql = 'SELECT * FROM orders';
    if ($period === 'today') {
        $sql .= ' WHERE DATE(created_at) = CURDATE()';
    } elseif ($period === 'week') {
        $sql .= ' WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)';
    } elseif ($period === 'month') {
        $sql .= ' WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)';
    }

    $orders    = $db->query($sql)->fetchAll();
    $totalRev  = array_sum(array_map(fn($o) => $o['status'] === 'collected' ? (float)$o['total'] : 0, $orders));
    $totalOrds = count($orders);

    $av = $db->query('SELECT COUNT(*) AS cnt FROM vendors WHERE status=\'active\'')->fetch();
    $od = $db->query('SELECT COUNT(*) AS cnt FROM disputes WHERE status=\'open\'')->fetch();

    $vendors    = $db->query('SELECT id, name FROM vendors')->fetchAll();
    $vendorRevs = [];
    foreach ($vendors as $v) {
        $vOrders      = array_filter($orders, fn($o) => (int)$o['vendor_id'] === (int)$v['id'] && $o['status'] === 'collected');
        $vendorRevs[] = [
            'vendorId'   => (int)$v['id'],
            'vendorName' => $v['name'],
            'revenue'    => array_sum(array_map(fn($o) => (float)$o['total'], $vOrders)),
            'orderCount' => count($vOrders),
        ];
    }
    usort($vendorRevs, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

    return jsonResponse($res, [
        'totalOrders'   => $totalOrds,
        'totalRevenue'  => $totalRev,
        'activeVendors' => (int) $av['cnt'],
        'openDisputes'  => (int) $od['cnt'],
        'vendorRevenue' => $vendorRevs,
    ]);
})->add($adminOnly);

// ── GET /api/disputes ─────────────────────────────────────────────────────────
$app->get('/api/disputes', function (Request $req, Response $res) {
    $db     = getDB();
    $status = $req->getQueryParams()['status'] ?? null;
    $sql    = 'SELECT d.*, u.name AS reporter_name FROM disputes d JOIN users u ON u.id=d.reported_by';
    $args   = [];
    if ($status) { $sql .= ' WHERE d.status=?'; $args[] = $status; }
    $sql   .= ' ORDER BY d.created_at DESC';
    $stmt   = $db->prepare($sql); $stmt->execute($args);
    return jsonResponse($res, array_map(fn($d) => [
        'id'           => (int)$d['id'],
        'orderId'      => (int)$d['order_id'],
        'reportedBy'   => (int)$d['reported_by'],
        'reporterName' => $d['reporter_name'],
        'description'  => $d['description'],
        'status'       => $d['status'],
        'resolution'   => $d['resolution'],
        'createdAt'    => $d['created_at'],
    ], $stmt->fetchAll()));
})->add($adminOnly);

// ── PATCH /api/disputes/{id}/resolve ─────────────────────────────────────────
$app->patch('/api/disputes/{id}/resolve', function (Request $req, Response $res, array $args) {
    $body       = (array) $req->getParsedBody();
    $resolution = trim($body['resolution'] ?? '');
    if (!$resolution) return jsonResponse($res, ['error' => 'Resolution required'], 400);

    $db = getDB();
    $id = (int) $args['id'];
    $db->prepare('UPDATE disputes SET status=\'resolved\', resolution=? WHERE id=?')->execute([$resolution, $id]);
    $stmt = $db->prepare('SELECT d.*, u.name AS reporter_name FROM disputes d JOIN users u ON u.id=d.reported_by WHERE d.id=?');
    $stmt->execute([$id]);
    $d = $stmt->fetch();
    if (!$d) return jsonResponse($res, ['error' => 'Dispute not found'], 404);
    return jsonResponse($res, [
        'id'           => (int)$d['id'],
        'orderId'      => (int)$d['order_id'],
        'reportedBy'   => (int)$d['reported_by'],
        'reporterName' => $d['reporter_name'],
        'description'  => $d['description'],
        'status'       => $d['status'],
        'resolution'   => $d['resolution'],
        'createdAt'    => $d['created_at'],
    ]);
})->add($adminOnly);