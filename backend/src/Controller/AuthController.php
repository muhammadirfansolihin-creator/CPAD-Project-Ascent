<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController {
    private UserRepository $users;

    public function __construct(UserRepository $users) {
        $this->users = $users;
    }

    private function json(Response $res, mixed $data, int $status = 200): Response {
        $res->getBody()->write(json_encode($data));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    private function makeToken(int $userId, string $role): string {
        $secret = getenv('JWT_SECRET') ?: 'campuseats-jwt-secret-2025';
        return JWT::encode([
            'sub'  => $userId,
            'role' => $role,
            'iat'  => time(),
            'exp'  => time() + 7 * 24 * 3600
        ], $secret, 'HS256');
    }

    private function formatUser(array $u): array {
        return ['id' => (int)$u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role'], 'createdAt' => $u['created_at']];
    }

    public function register(Request $req, Response $res): Response {
        $body = (array)$req->getParsedBody();
        $name = trim($body['name'] ?? '');
        $email = trim($body['email'] ?? '');
        $pass = $body['password'] ?? '';
        $role = $body['role'] ?? 'student';

        if (!$name || !$email || !$pass) {
            return $this->json($res, ['error' => 'Missing fields'], 400);
        }
        if ($this->users->findByEmail($email)) {
            return $this->json($res, ['error' => 'Email taken'], 400);
        }

        $id = $this->users->create($name, $email, password_hash($pass, PASSWORD_BCRYPT), $role);
        $user = $this->users->findById($id);

        return $this->json($res, ['token' => $this->makeToken($id, $role), 'user' => $this->formatUser($user)], 201);
    }

    public function login(Request $req, Response $res): Response {
        $body = (array)$req->getParsedBody();
        $email = trim($body['email'] ?? '');
        $pass = $body['password'] ?? '';

        if (!$email || !$pass) {
            return $this->json($res, ['error' => 'Email and password required'], 400);
        }

        $user = $this->users->findByEmail($email);
        if (!$user || !password_verify($pass, $user['password_hash'])) {
            return $this->json($res, ['error' => 'Invalid credentials'], 401);
        }

        return $this->json($res, ['token' => $this->makeToken((int)$user['id'], $user['role']), 'user' => $this->formatUser($user)]);
    }

    public function me(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $user = $this->users->findById($userId);
        if (!$user) {
            return $this->json($res, ['error' => 'User not found'], 404);
        }
        return $this->json($res, $this->formatUser($user));
    }

    public function stats(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        return $this->json($res, $this->users->getStats($userId));
    }
}