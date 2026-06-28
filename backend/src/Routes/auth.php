<?php
declare(strict_types=1);

use App\Database;
use App\Controller\AuthController;
use App\Repositories\UserRepository;
use App\Middleware\AuthMiddleware;

global $app;

$db = Database::get();
$userRepo = new UserRepository($db);
$authController = new AuthController($userRepo);

$app->post('/api/auth/register', [$authController, 'register']);
$app->post('/api/auth/login', [$authController, 'login']);
$app->get('/api/auth/me', [$authController, 'me'])->add(new AuthMiddleware());
$app->get('/api/profile/stats', [$authController, 'stats'])->add(new AuthMiddleware());