<?php
declare(strict_types=1);

use App\Middleware\AuthMiddleware;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

global $app;

// ── Helper ────────────────────────────────────────────────────────────────────
function makeToken(int $userId, string $role): string {
    $secret = getenv('JWT_SECRET') ?: 'campuseats-jwt-secret-2025';
    $payload = [
        'sub'  => $userId,
        'role' => $role,
        'iat'  => time(),
        'exp'  => time() + 7 * 24 * 3600,
    ];
    return JWT::encode($payload, $secret, 'HS256');
}

function userRow(array $u): array {
    return ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'createdAt' => $u['created_at']];
}

function jsonResponse(Response $res, mixed $data, int $status = 200): Response {
    $res->getBody()->write(json_encode($data));
    return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
}

// ── POST /api/auth/register ───────────────────────────────────────────────────
$app->post('/api/auth/register', function (Request $req, Response $res) {
    $body = (array) $req->getParsedBody();
    $name  = trim($body['name']  ?? '');
    $email = trim($body['email'] ?? '');
    $pass  = $body['password']   ?? '';
    $role  = $body['role']       ?? 'student';

    if (!$name || !$email || !$pass || !in_array($role, ['student', 'vendor'])) {
        return jsonResponse($res, ['error' => 'All fields required (role: student or vendor)'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return jsonResponse($res, ['error' => 'Invalid email address'], 400);
    }

    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        return jsonResponse($res, ['error' => 'Email already registered'], 409);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);

    $db->beginTransaction();
    try {
        $ins = $db->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
        $ins->execute([$name, $email, $hash, $role]);
        $id = (int) $db->lastInsertId();

        // When registering as a vendor, create the vendor profile row immediately.
        // Status starts as 'pending' so the admin must approve before the stall goes live.
        // This ensures every vendor has a unique vendors record and existing vendors are never affected.
        if ($role === 'vendor') {
            $vins = $db->prepare(
                'INSERT INTO vendors (owner_id, name, location, opening_hours, is_active, is_open, status)
                 VALUES (?, ?, ?, ?, 0, 0, \'pending\')'
            );
            $vins->execute([$id, $name . '\'s Stall', 'TBD', 'TBD']);
        }

        $db->commit();
    } catch (\Throwable $e) {
        $db->rollBack();
        return jsonResponse($res, ['error' => 'Registration failed. Please try again.'], 500);
    }

    $user = ['id' => $id, 'name' => $name, 'email' => $email, 'role' => $role, 'created_at' => date('Y-m-d H:i:s')];
    return jsonResponse($res, ['token' => makeToken($id, $role), 'user' => userRow($user)], 201);
});

// ── POST /api/auth/login ──────────────────────────────────────────────────────
$app->post('/api/auth/login', function (Request $req, Response $res) {
    $body  = (array) $req->getParsedBody();
    $email = trim($body['email']    ?? '');
    $pass  = $body['password'] ?? '';

    if (!$email || !$pass) {
        return jsonResponse($res, ['error' => 'Email and password required'], 400);
    }

    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($pass, $user['password_hash'])) {
        return jsonResponse($res, ['error' => 'Invalid credentials'], 401);
    }

    return jsonResponse($res, ['token' => makeToken((int) $user['id'], $user['role']), 'user' => userRow($user)]);
});

// ── GET /api/auth/me ──────────────────────────────────────────────────────────
$app->get('/api/auth/me', function (Request $req, Response $res) {
    $userId = $req->getAttribute('userId');
    $db     = getDB();
    $stmt   = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) return jsonResponse($res, ['error' => 'User not found'], 404);
    return jsonResponse($res, userRow($user));
})->add(new AuthMiddleware());