<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

global $app;

// ── GET /api/vendors ──────────────────────────────────────────────────────────
$app->get('/api/vendors', function (Request $req, Response $res) {
    $db     = getDB();
    $params = $req->getQueryParams();
    $search = $params['search'] ?? '';

    $sql  = 'SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.owner_id WHERE v.status = \'active\'';
    $args = [];
    if ($search) { $sql .= ' AND (v.name LIKE ? OR v.location LIKE ?)'; $args = ["%$search%", "%$search%"]; }
    $stmt = $db->prepare($sql);
    $stmt->execute($args);
    $vendors = $stmt->fetchAll();

    $result = [];
    foreach ($vendors as $v) {
        $r = $db->prepare('SELECT AVG(rating) AS avg FROM reviews WHERE vendor_id = ?');
        $r->execute([(int)$v['id']]);
        $rRow = $r->fetch();

        $c = $db->prepare('SELECT COUNT(*) AS cnt FROM orders WHERE vendor_id = ?');
        $c->execute([(int)$v['id']]);
        $cRow = $c->fetch();

        // FIX: build result array cleanly — do NOT unset then access the same key
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
            'rating'       => $rRow['avg'] !== null ? round((float)$rRow['avg'], 1) : null,
            'totalOrders'  => (int)$cRow['cnt'],
            'createdAt'    => $v['created_at'],
        ];
    }
    return jsonResponse($res, $result);
});

// ── GET /api/vendors/{id} ─────────────────────────────────────────────────────
$app->get('/api/vendors/{id}', function (Request $req, Response $res, array $args) {
    $db   = getDB();
    $id   = (int) $args['id'];
    $stmt = $db->prepare('SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.owner_id WHERE v.id = ?');
    $stmt->execute([$id]);
    $v = $stmt->fetch();
    if (!$v) return jsonResponse($res, ['error' => 'Vendor not found'], 404);

    $r = $db->prepare('SELECT AVG(rating) AS avg FROM reviews WHERE vendor_id = ?');
    $r->execute([$id]);
    $rRow = $r->fetch();

    $c = $db->prepare('SELECT COUNT(*) AS cnt FROM orders WHERE vendor_id = ?');
    $c->execute([$id]);
    $cRow = $c->fetch();

    return jsonResponse($res, [
        'id'           => (int) $v['id'],
        'ownerId'      => (int) $v['owner_id'],
        'ownerName'    => $v['owner_name'],
        'name'         => $v['name'],
        'location'     => $v['location'],
        'openingHours' => $v['opening_hours'],
        'imageUrl'     => $v['image_url'],
        'isActive'     => (bool) $v['is_active'],
        'isOpen'       => (bool) $v['is_open'],
        'status'       => $v['status'],
        'rating'       => $rRow['avg'] !== null ? round((float)$rRow['avg'], 1) : null,
        'totalOrders'  => (int) $cRow['cnt'],
        'createdAt'    => $v['created_at'],
    ]);
});

// ── GET /api/vendors/{id}/menu ────────────────────────────────────────────────
$app->get('/api/vendors/{id}/menu', function (Request $req, Response $res, array $args) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM menu_items WHERE vendor_id = ?');
    $stmt->execute([(int) $args['id']]);
    $items = $stmt->fetchAll();
    return jsonResponse($res, array_map(fn($i) => [
        'id'          => (int) $i['id'],
        'vendorId'    => (int) $i['vendor_id'],
        'name'        => $i['name'],
        'description' => $i['description'],
        'price'       => (float) $i['price'],
        'category'    => $i['category'],
        'imageUrl'    => $i['image_url'],
        'inStock'     => (bool) $i['in_stock'],
        'isAvailable' => (bool) $i['in_stock'],
    ], $items));
});

// ── GET /api/vendors/{id}/reviews ─────────────────────────────────────────────
$app->get('/api/vendors/{id}/reviews', function (Request $req, Response $res, array $args) {
    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT r.*, u.name AS user_name FROM reviews r JOIN users u ON u.id = r.user_id WHERE r.vendor_id = ? ORDER BY r.created_at DESC'
    );
    $stmt->execute([(int) $args['id']]);
    $rows = $stmt->fetchAll();
    return jsonResponse($res, array_map(fn($r) => [
        'id'        => (int) $r['id'],
        'userId'    => (int) $r['user_id'],
        'vendorId'  => (int) $r['vendor_id'],
        'userName'  => $r['user_name'],
        'rating'    => (int) $r['rating'],
        'comment'   => $r['comment'],
        'createdAt' => $r['created_at'],
    ], $rows));
});

// ── POST /api/vendors/{id}/reviews ────────────────────────────────────────────
$app->post('/api/vendors/{id}/reviews', function (Request $req, Response $res, array $args) {
    $body   = (array) $req->getParsedBody();
    $rating = (int) ($body['rating'] ?? 0);
    if ($rating < 1 || $rating > 5) return jsonResponse($res, ['error' => 'Rating must be 1–5'], 400);

    $db       = getDB();
    $userId   = (int) $req->getAttribute('userId');
    $vendorId = (int) $args['id'];
    $ins      = $db->prepare('INSERT INTO reviews (user_id, vendor_id, rating, comment) VALUES (?,?,?,?)');
    $ins->execute([$userId, $vendorId, $rating, $body['comment'] ?? null]);
    $id       = (int) $db->lastInsertId();

    $u = $db->prepare('SELECT name FROM users WHERE id = ?'); $u->execute([$userId]);
    return jsonResponse($res, [
        'id' => $id, 'userId' => $userId, 'vendorId' => $vendorId,
        'userName' => $u->fetch()['name'], 'rating' => $rating,
        'comment'  => $body['comment'] ?? null, 'createdAt' => date('Y-m-d H:i:s'),
    ], 201);
})->add(new AuthMiddleware());

// ── POST /api/orders ──────────────────────────────────────────────────────────
$app->post('/api/orders', function (Request $req, Response $res) {
    $body     = (array) $req->getParsedBody();
    $vendorId = (int) ($body['vendorId'] ?? 0);
    $pickupAt = $body['pickupAt'] ?? '';
    $items    = $body['items']    ?? [];

    if (!$vendorId || !$pickupAt || !$items) {
        return jsonResponse($res, ['error' => 'Missing required fields'], 400);
    }

    $db     = getDB();
    $userId = (int) $req->getAttribute('userId');
    $total  = 0.0;
    $rows   = [];

    foreach ($items as $item) {
        $stmt = $db->prepare('SELECT * FROM menu_items WHERE id = ?');
        $stmt->execute([(int) $item['menuItemId']]);
        $mi = $stmt->fetch();
        if (!$mi) return jsonResponse($res, ['error' => "Menu item {$item['menuItemId']} not found"], 400);
        $qty       = (int) $item['qty'];
        $unitPrice = (float) $mi['price'];
        $total    += $unitPrice * $qty;
        $rows[]    = ['menuItemId' => (int) $mi['id'], 'name' => $mi['name'], 'qty' => $qty, 'unitPrice' => $unitPrice];
    }

    $ins = $db->prepare('INSERT INTO orders (user_id, vendor_id, status, total, pickup_at) VALUES (?,?,?,?,?)');
    $ins->execute([$userId, $vendorId, 'placed', number_format($total, 2, '.', ''), $pickupAt]);
    $orderId = (int) $db->lastInsertId();

    foreach ($rows as $r) {
        $ii = $db->prepare('INSERT INTO order_items (order_id, menu_item_id, name, qty, unit_price) VALUES (?,?,?,?,?)');
        $ii->execute([$orderId, $r['menuItemId'], $r['name'], $r['qty'], number_format($r['unitPrice'], 2, '.', '')]);
    }

     // Notify the vendor owner about the new order
    $vOwner = $db->prepare('SELECT owner_id FROM vendors WHERE id = ?');
    $vOwner->execute([$vendorId]);
    $vendorRow = $vOwner->fetch();
    if ($vendorRow) {
        $notifMsg = "New order #{$orderId} received.";
        $db->prepare('INSERT INTO notifications (user_id, order_id, message) VALUES (?,?,?)')
           ->execute([(int) $vendorRow['owner_id'], $orderId, $notifMsg]);
    }

    $v  = $db->prepare('SELECT name FROM vendors WHERE id = ?'); $v->execute([$vendorId]);
    $cu = $db->prepare('SELECT name FROM users   WHERE id = ?'); $cu->execute([$userId]);

    return jsonResponse($res, [
        'id'           => $orderId,
        'userId'       => $userId,
        'vendorId'     => $vendorId,
        'vendorName'   => $v->fetch()['name'],
        'customerName' => $cu->fetch()['name'],
        'status'       => 'placed',
        'total'        => $total,
        'pickupAt'     => $pickupAt,
        'createdAt'    => date('Y-m-d H:i:s'),
        'items'        => array_map(fn($r) => array_merge(['id' => 0], $r), $rows),
    ], 201);
})->add(new AuthMiddleware());

// ── GET /api/orders ───────────────────────────────────────────────────────────
$app->get('/api/orders', function (Request $req, Response $res) {
    $db     = getDB();
    $userId = (int) $req->getAttribute('userId');
    $role   = $req->getAttribute('role');
    $qp     = $req->getQueryParams();

    if ($role === 'student') {
        $stmt = $db->prepare('SELECT o.*, v.name AS vendor_name, u.name AS customer_name FROM orders o JOIN vendors v ON v.id = o.vendor_id JOIN users u ON u.id = o.user_id WHERE o.user_id = ? ORDER BY o.created_at DESC');
        $stmt->execute([$userId]);
    } elseif ($role === 'vendor') {
        $vid  = (int) ($qp['vendorId'] ?? 0);
        $stmt = $db->prepare('SELECT o.*, v.name AS vendor_name, u.name AS customer_name FROM orders o JOIN vendors v ON v.id = o.vendor_id JOIN users u ON u.id = o.user_id WHERE o.vendor_id = ? ORDER BY o.created_at DESC');
        $stmt->execute([$vid]);
    } else {
        $stmt = $db->prepare('SELECT o.*, v.name AS vendor_name, u.name AS customer_name FROM orders o JOIN vendors v ON v.id = o.vendor_id JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC');
        $stmt->execute([]);
    }

    $orders = $stmt->fetchAll();
    $result = [];
    foreach ($orders as $o) {
        $items = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([(int) $o['id']]);
        $result[] = [
            'id' => (int)$o['id'], 'userId' => (int)$o['user_id'], 'vendorId' => (int)$o['vendor_id'],
            'vendorName' => $o['vendor_name'], 'customerName' => $o['customer_name'],
            'status' => $o['status'], 'total' => (float)$o['total'],
            'pickupAt' => $o['pickup_at'], 'createdAt' => $o['created_at'],
            'items' => array_map(fn($i) => ['id'=>(int)$i['id'],'menuItemId'=>(int)$i['menu_item_id'],'name'=>$i['name'],'qty'=>(int)$i['qty'],'unitPrice'=>(float)$i['unit_price']], $items->fetchAll()),
        ];
    }
    return jsonResponse($res, $result);
})->add(new AuthMiddleware());

// ── GET /api/orders/{id} ──────────────────────────────────────────────────────
$app->get('/api/orders/{id}', function (Request $req, Response $res, array $args) {
    $db   = getDB();
    $id   = (int) $args['id'];
    $stmt = $db->prepare('SELECT o.*, v.name AS vendor_name, u.name AS customer_name FROM orders o JOIN vendors v ON v.id = o.vendor_id JOIN users u ON u.id = o.user_id WHERE o.id = ?');
    $stmt->execute([$id]);
    $o = $stmt->fetch();
    if (!$o) return jsonResponse($res, ['error' => 'Order not found'], 404);
    $items = $db->prepare('SELECT * FROM order_items WHERE order_id = ?'); $items->execute([$id]);
    return jsonResponse($res, [
        'id' => (int)$o['id'], 'userId' => (int)$o['user_id'], 'vendorId' => (int)$o['vendor_id'],
        'vendorName' => $o['vendor_name'], 'customerName' => $o['customer_name'],
        'status' => $o['status'], 'total' => (float)$o['total'],
        'pickupAt' => $o['pickup_at'], 'createdAt' => $o['created_at'],
        'items' => array_map(fn($i) => ['id'=>(int)$i['id'],'menuItemId'=>(int)$i['menu_item_id'],'name'=>$i['name'],'qty'=>(int)$i['qty'],'unitPrice'=>(float)$i['unit_price']], $items->fetchAll()),
    ]);
})->add(new AuthMiddleware());

// ── POST /api/disputes ────────────────────────────────────────────────────────
$app->post('/api/disputes', function (Request $req, Response $res) {
    $body        = (array) $req->getParsedBody();
    $orderId     = (int) ($body['orderId'] ?? 0);
    $description = trim($body['description'] ?? '');
    if (!$orderId || !$description) return jsonResponse($res, ['error' => 'orderId and description required'], 400);

    $db     = getDB();
    $userId = (int) $req->getAttribute('userId');
    $ins    = $db->prepare('INSERT INTO disputes (order_id, reported_by, description) VALUES (?,?,?)');
    $ins->execute([$orderId, $userId, $description]);
    $id     = (int) $db->lastInsertId();

    // Notify all admins about new dispute
    $admins = $db->prepare('SELECT id FROM users WHERE role = ?');
    $admins->execute(['admin']);
    foreach ($admins->fetchAll() as $admin) {
        $db->prepare('INSERT INTO notifications (user_id, order_id, message) VALUES (?,?,?)')
        ->execute([(int) $admin['id'], $orderId, "New dispute filed for Order #{$orderId}."]);
    }

    $u = $db->prepare('SELECT name FROM users WHERE id = ?'); $u->execute([$userId]);
    return jsonResponse($res, [
        'id' => $id, 'orderId' => $orderId, 'reportedBy' => $userId,
        'reporterName' => $u->fetch()['name'], 'description' => $description,
        'status' => 'open', 'resolution' => null, 'createdAt' => date('Y-m-d H:i:s'),
    ], 201);
})->add(new AuthMiddleware());