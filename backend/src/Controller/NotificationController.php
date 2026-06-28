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

        $res->getBody()->write(json_encode($payload));
        return $res->withHeader('Content-Type', 'application/json');
    }

    public function markRead(Request $req, Response $res, array $args): Response {
        $id = (int)$args['id'];
        $userId = (int)$req->getAttribute('userId');

        if (!$this->repo->verifyOwnership($id, $userId)) {
            $res->getBody()->write(json_encode(['error' => 'Not found or access denied']));
            return $res->withHeader('Content-Type', 'application/json')->withStatus(404);
        }

        $this->repo->markAsRead($id);
        $res->getBody()->write(json_encode(['success' => true]));
        return $res->withHeader('Content-Type', 'application/json');
    }
}