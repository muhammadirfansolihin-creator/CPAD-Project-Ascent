<?php
declare(strict_types=1);

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AuthMiddleware implements MiddlewareInterface {
    private string $secret;
    private ?string $requiredRole;

    public function __construct(?string $requiredRole = null) {
        $this->secret       = getenv('JWT_SECRET') ?: 'campuseats-jwt-secret-2025';
        $this->requiredRole = $requiredRole;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $authHeader = $request->getHeaderLine('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->unauthorized('Missing or invalid Authorization header');
        }

        $token = substr($authHeader, 7);
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
        } catch (\Throwable $e) {
            return $this->unauthorized('Invalid or expired token');
        }

        if ($this->requiredRole && ($decoded->role ?? '') !== $this->requiredRole) {
            return $this->forbidden('Access denied for role: ' . ($decoded->role ?? 'unknown'));
        }

        $request = $request
            ->withAttribute('userId', $decoded->sub)
            ->withAttribute('role',   $decoded->role);

        return $handler->handle($request);
    }

    private function unauthorized(string $msg): ResponseInterface {
        $response = new Response(401);
        $response->getBody()->write(json_encode(['error' => $msg]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function forbidden(string $msg): ResponseInterface {
        $response = new Response(403);
        $response->getBody()->write(json_encode(['error' => $msg]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
