<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

global $app;

// ── POST /api/vendors ─────────────────────────────────────────────────────────
$app->post('/api/vendors', function (Request $req, Response $res) {
    $body  = (array) $req->getParsedBody();
    $name  = trim($body['name']         ?? '');
    $loc   = trim($body['location']     ?? '');
    $hours = trim($body['openingHours'] ?? '');
    if (!$name || !$loc || !$hours) return jsonResponse($res, ['error' => 'name, location, openingHours required'], 400);

    $db     = getDB();
    $userId = (int) $req->getAttribute('userId');
    $ins    = $db->prepare('INSERT INTO vendors (owner_id, name, location, opening_hours, image_url) VALUES (?,?,?,?,?)');
    $ins->execute([$userId, $name, $loc, $hours, $body['imageUrl'] ?? null]);
    $id     = (int) $db->lastInsertId();

    $u = $db->prepare('SELECT name FROM users WHERE id = ?'); $u->execute([$userId]);
    return jsonResponse($res, [
        'id' => $id, 'ownerId' => $userId, 'ownerName' => $u->fetch()['name'],
        'name' => $name, 'location' => $loc, 'openingHours' => $hours,
        'imageUrl' => $body['imageUrl'] ?? null, 'isActive' => false, 'isOpen' => false,
        'status' => 'pending', 'rating' => null, 'totalOrders' => 0, 'createdAt' => date('Y-m-d H:i:s'),
    ], 201);
})->add(new AuthMiddleware());

// ── PUT /api/vendors/{id} ─────────────────────────────────────────────────────
$app->put('/api/vendors/{id}', function (Request $req, Response $res, array $args) {
    $body  = (array) $req->getParsedBody();
    $db    = getDB();
    $id    = (int) $args['id'];
    $upd   = $db->prepare('UPDATE vendors SET name=?, location=?, opening_hours=?, image_url=? WHERE id=?');
    $upd->execute([$body['name'] ?? '', $body['location'] ?? '', $body['openingHours'] ?? '', $body['imageUrl'] ?? null, $id]);
    $stmt  = $db->prepare('SELECT * FROM vendors WHERE id=?'); $stmt->execute([$id]);
    $v = $stmt->fetch();
    return jsonResponse($res, ['id' => (int)$v['id'], 'ownerId' => (int)$v['owner_id'], 'ownerName' => null,
        'name' => $v['name'], 'location' => $v['location'], 'openingHours' => $v['opening_hours'],
        'imageUrl' => $v['image_url'], 'isActive' => (bool)$v['is_active'], 'isOpen' => (bool)$v['is_open'],
        'status' => $v['status'], 'rating' => null, 'totalOrders' => 0, 'createdAt' => $v['created_at']]);
})->add(new AuthMiddleware());

// ── PATCH /api/vendors/{id}/toggle-open ───────────────────────────────────────
$app->patch('/api/vendors/{id}/toggle-open', function (Request $req, Response $res, array $args) {
    $db   = getDB();
    $id   = (int) $args['id'];
    $cur  = $db->prepare('SELECT is_open FROM vendors WHERE id=?'); $cur->execute([$id]);
    $row  = $cur->fetch();
    if (!$row) return jsonResponse($res, ['error' => 'Vendor not found'], 404);
    $next = $row['is_open'] ? 0 : 1;
    $db->prepare('UPDATE vendors SET is_open=? WHERE id=?')->execute([$next, $id]);
    $stmt = $db->prepare('SELECT * FROM vendors WHERE id=?'); $stmt->execute([$id]);
    $v    = $stmt->fetch();
    return jsonResponse($res, ['id' => (int)$v['id'], 'ownerId' => (int)$v['owner_id'], 'ownerName' => null,
        'name' => $v['name'], 'location' => $v['location'], 'openingHours' => $v['opening_hours'],
        'imageUrl' => $v['image_url'], 'isActive' => (bool)$v['is_active'], 'isOpen' => (bool)$v['is_open'],
        'status' => $v['status'], 'rating' => null, 'totalOrders' => 0, 'createdAt' => $v['created_at']]);
})->add(new AuthMiddleware());

// ── PATCH /api/orders/{id}/status ─────────────────────────────────────────────
$app->patch('/api/orders/{id}/status', function (Request $req, Response $res, array $args) {
    $body   = (array) $req->getParsedBody();
    $status = $body['status'] ?? '';
    $valid  = ['placed', 'preparing', 'ready', 'collected'];
    if (!in_array($status, $valid)) return jsonResponse($res, ['error' => 'Invalid status'], 400);

    $db  = getDB();
    $id  = (int) $args['id'];
    $db->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status, $id]);
    $stmt = $db->prepare('SELECT o.*, v.name AS vendor_name, u.name AS customer_name FROM orders o JOIN vendors v ON v.id=o.vendor_id JOIN users u ON u.id=o.user_id WHERE o.id=?');
    $stmt->execute([$id]);
    $o = $stmt->fetch();
    if (!$o) return jsonResponse($res, ['error' => 'Order not found'], 404);
    $items = $db->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([$id]);
    return jsonResponse($res, [
        'id' => (int)$o['id'], 'userId' => (int)$o['user_id'], 'vendorId' => (int)$o['vendor_id'],
        'vendorName' => $o['vendor_name'], 'customerName' => $o['customer_name'],
        'status' => $o['status'], 'total' => (float)$o['total'],
        'pickupAt' => $o['pickup_at'], 'createdAt' => $o['created_at'],
        'items' => array_map(fn($i) => ['id'=>(int)$i['id'],'menuItemId'=>(int)$i['menu_item_id'],'name'=>$i['name'],'qty'=>(int)$i['qty'],'unitPrice'=>(float)$i['unit_price']], $items->fetchAll()),
    ]);
})->add(new AuthMiddleware());

// ── POST /api/vendors/{id}/menu ───────────────────────────────────────────────
$app->post('/api/vendors/{id}/menu', function (Request $req, Response $res, array $args) {
    $body = (array) $req->getParsedBody();
    $name = trim($body['name'] ?? '');
    $price = (float) ($body['price'] ?? 0);

    // FIX: use ?: so empty string falls back to 'other' (not just null)
    $validCats = ['rice', 'noodles', 'drinks', 'snacks', 'other'];
    $cat = in_array($body['category'] ?? '', $validCats) ? $body['category'] : 'other';

    if (!$name || $price <= 0) return jsonResponse($res, ['error' => 'name and a positive price required'], 400);

    $db       = getDB();
    $vendorId = (int) $args['id'];
    $ins      = $db->prepare('INSERT INTO menu_items (vendor_id, name, description, price, category, image_url, in_stock) VALUES (?,?,?,?,?,?,1)');
    $ins->execute([$vendorId, $name, $body['description'] ?? null, number_format($price, 2, '.', ''), $cat, $body['imageUrl'] ?? null]);
    $id       = (int) $db->lastInsertId();
    return jsonResponse($res, [
        'id' => $id, 'vendorId' => $vendorId, 'name' => $name,
        'description' => $body['description'] ?? null,
        'price' => $price, 'category' => $cat,
        'imageUrl' => $body['imageUrl'] ?? null, 'inStock' => true,
        'isAvailable' => true,
    ], 201);
})->add(new AuthMiddleware());

// ── PUT /api/menu-items/{id} ──────────────────────────────────────────────────
$app->put('/api/menu-items/{id}', function (Request $req, Response $res, array $args) {
    $body = (array) $req->getParsedBody();
    $db   = getDB();
    $id   = (int) $args['id'];

    $validCats = ['rice', 'noodles', 'drinks', 'snacks', 'other'];
    $fields = []; $vals = [];
    if (isset($body['name']))        { $fields[] = 'name=?';        $vals[] = $body['name']; }
    if (isset($body['description'])) { $fields[] = 'description=?'; $vals[] = $body['description']; }
    if (isset($body['price']))       { $fields[] = 'price=?';       $vals[] = number_format((float)$body['price'], 2, '.', ''); }
    if (isset($body['category']) && in_array($body['category'], $validCats)) {
                                       $fields[] = 'category=?';    $vals[] = $body['category']; }
    if (isset($body['imageUrl']))    { $fields[] = 'image_url=?';   $vals[] = $body['imageUrl']; }
    if (isset($body['inStock']))     { $fields[] = 'in_stock=?';    $vals[] = $body['inStock'] ? 1 : 0; }
    if (isset($body['isAvailable'])) { $fields[] = 'in_stock=?';   $vals[] = $body['isAvailable'] ? 1 : 0; }

    if ($fields) { $vals[] = $id; $db->prepare('UPDATE menu_items SET '.implode(',', $fields).' WHERE id=?')->execute($vals); }

    $stmt = $db->prepare('SELECT * FROM menu_items WHERE id=?'); $stmt->execute([$id]);
    $i    = $stmt->fetch();
    if (!$i) return jsonResponse($res, ['error' => 'Menu item not found'], 404);
    return jsonResponse($res, [
        'id' => (int)$i['id'], 'vendorId' => (int)$i['vendor_id'], 'name' => $i['name'],
        'description' => $i['description'], 'price' => (float)$i['price'], 'category' => $i['category'],
        'imageUrl' => $i['image_url'], 'inStock' => (bool)$i['in_stock'], 'isAvailable' => (bool)$i['in_stock'],
    ]);
})->add(new AuthMiddleware());

// ── DELETE /api/menu-items/{id} ───────────────────────────────────────────────
$app->delete('/api/menu-items/{id}', function (Request $req, Response $res, array $args) {
    getDB()->prepare('DELETE FROM menu_items WHERE id=?')->execute([(int)$args['id']]);
    return $res->withStatus(204);
})->add(new AuthMiddleware());

// ── PATCH /api/menu-items/{id}/stock ─────────────────────────────────────────
$app->patch('/api/menu-items/{id}/stock', function (Request $req, Response $res, array $args) {
    $db  = getDB();
    $id  = (int) $args['id'];
    $cur = $db->prepare('SELECT * FROM menu_items WHERE id=?'); $cur->execute([$id]);
    $i   = $cur->fetch();
    if (!$i) return jsonResponse($res, ['error' => 'Not found'], 404);
    $newStock = $i['in_stock'] ? 0 : 1;
    $db->prepare('UPDATE menu_items SET in_stock=? WHERE id=?')->execute([$newStock, $id]);
    $cur2 = $db->prepare('SELECT * FROM menu_items WHERE id=?'); $cur2->execute([$id]);
    $i    = $cur2->fetch();
    return jsonResponse($res, [
        'id' => (int)$i['id'], 'vendorId' => (int)$i['vendor_id'], 'name' => $i['name'],
        'description' => $i['description'], 'price' => (float)$i['price'], 'category' => $i['category'],
        'imageUrl' => $i['image_url'], 'inStock' => (bool)$i['in_stock'], 'isAvailable' => (bool)$i['in_stock'],
    ]);
})->add(new AuthMiddleware());

// ── GET /api/vendor/dashboard ─────────────────────────────────────────────────
$app->get('/api/vendor/dashboard', function (Request $req, Response $res) {
    $db     = getDB();
    $userId = (int) $req->getAttribute('userId');
    $vstmt  = $db->prepare('SELECT * FROM vendors WHERE owner_id=?'); $vstmt->execute([$userId]);
    $vendor = $vstmt->fetch();
    if (!$vendor) return jsonResponse($res, ['error' => 'Vendor not found'], 404);
    $vendorId = (int) $vendor['id'];

    $today     = date('Y-m-d');
    $allOrders = $db->prepare('SELECT * FROM orders WHERE vendor_id=? ORDER BY created_at DESC'); $allOrders->execute([$vendorId]);
    $orders    = $allOrders->fetchAll();

    $dailyRevenue = 0.0;
    $activeOrders = [];
    $recentOrders = [];
    foreach ($orders as $o) {
        if (str_starts_with($o['created_at'], $today) && $o['status'] === 'collected') $dailyRevenue += (float)$o['total'];
        if (in_array($o['status'], ['placed', 'preparing', 'ready'])) $activeOrders[] = $o;
        if (count($recentOrders) < 10) $recentOrders[] = $o;
    }

    $r   = $db->prepare('SELECT AVG(rating) AS avg FROM reviews WHERE vendor_id=?'); $r->execute([$vendorId]);
    $avg = $r->fetch()['avg'];

    // FIX: use anonymous function instead of named function to avoid "Cannot redeclare" fatal error
    $enrichOrder = function (array $o) use ($db): array {
        $items = $db->prepare('SELECT * FROM order_items WHERE order_id=?'); $items->execute([(int)$o['id']]);
        $u     = $db->prepare('SELECT name FROM users WHERE id=?');          $u->execute([(int)$o['user_id']]);
        $v     = $db->prepare('SELECT name FROM vendors WHERE id=?');        $v->execute([(int)$o['vendor_id']]);
        return [
            'id'           => (int)$o['id'],
            'userId'       => (int)$o['user_id'],
            'vendorId'     => (int)$o['vendor_id'],
            'vendorName'   => $v->fetch()['name'],
            'studentName'  => $u->fetch()['name'],
            'status'       => $o['status'],
            'total'        => (float)$o['total'],
            'pickupTime'   => $o['pickup_at'],
            'createdAt'    => $o['created_at'],
            'items'        => array_map(fn($i) => [
                'id'         => (int)$i['id'],
                'menuItemId' => (int)$i['menu_item_id'],
                'name'       => $i['name'],
                'quantity'   => (int)$i['qty'],
                'unitPrice'  => (float)$i['unit_price'],
            ], $items->fetchAll()),
        ];
    };

    return jsonResponse($res, [
        'dailyRevenue'  => $dailyRevenue,
        'totalOrders'   => count($orders),
        'activeOrders'  => count($activeOrders),
        'avgRating'     => $avg !== null ? round((float)$avg, 1) : null,
        'liveQueue'     => array_map($enrichOrder, $activeOrders),
        'recentOrders'  => array_map($enrichOrder, $recentOrders),
    ]);
})->add(new AuthMiddleware());