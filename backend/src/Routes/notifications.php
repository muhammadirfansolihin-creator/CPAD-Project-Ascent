<?php
declare(strict_types=1);

use App\Database;
use App\Controller\NotificationController;
use App\Repositories\NotificationRepository;
use App\Middleware\AuthMiddleware;

global $app;

$db = Database::get();
$notifRepo = new NotificationRepository($db);
$notifController = new NotificationController($notifRepo);

$app->get('/api/notifications', [$notifController, 'getNotifications'])->add(new AuthMiddleware());
$app->put('/api/notifications/{id}/read', [$notifController, 'markRead'])->add(new AuthMiddleware());