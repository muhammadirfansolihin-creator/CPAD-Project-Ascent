<?php
declare(strict_types=1);

namespace App\Controller;

use App\Repositories\NotificationRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NotificationController {
    private NotificationRepository $repo;

    public function __construct(NotificationRepository $repo) {
        $this->repo = $repo;
    }

    private function json(Response $res, mixed $data, int $status = 200): Response {
        $res->getBody()->write(json_encode($data,
            JSON_UNESCAPED_UNICODE| JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
        ));
        return $res->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function getNotifications(Request $req, Response $res): Response {
        $userId = (int)$req->getAttribute('userId');
        $rows = $this->repo->getByUserId($userId);

        $payload = array_map(fn($n) => [
            'id'        => (int)$n['id'],
            'orderId'   => (int)$n['order_id'],
            'message'   => $n['message'],
            'isRead'    => (bool)$n['is_read'],
            'createdAt' => $n['created_at'],
        ], $rows);

        return $this->json($res, $payload);
    }

    public function markRead(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $userId = (int)$req->getAttribute('userId');

        if (!$this->repo->verifyOwnership($id, $userId)) {
            return $this->json($res, ['error' => 'Not found or access denied'], 404);
        }

        $this->repo->markAsRead($id);
        return $this->json($res, ['success' => true]);
    }
}