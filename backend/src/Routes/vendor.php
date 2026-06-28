<?php
declare(strict_types=1);

use App\Database;
use App\Controller\VendorController;
use App\Repositories\VendorRepository;
use App\Repositories\OrderRepository;
use App\Middleware\AuthMiddleware;

global $app;

$db = Database::get();
$vendorRepo = new VendorRepository($db);
$orderRepo = new OrderRepository($db);

$vendorController = new VendorController($vendorRepo, $orderRepo);

$app->post('/api/vendor', [$vendorController, 'createVendor'])->add(new AuthMiddleware('vendor'));
$app->get('/api/vendor/dashboard', [$vendorController, 'getDashboard'])->add(new AuthMiddleware('vendor'));
$app->put('/api/orders/{id}/status', [$vendorController, 'updateOrderStatus'])->add(new AuthMiddleware('vendor'));
$app->get('/api/vendor/profile', [$vendorController, 'getProfile'])->add(new AuthMiddleware('vendor'));
$app->put('/api/vendor/{id}', [$vendorController, 'updateProfile'])->add(new AuthMiddleware('vendor'));
$app->get('/api/vendor/menu', [$vendorController, 'getMenu'])->add(new AuthMiddleware('vendor'));
$app->get('/api/vendor/orders', [$vendorController, 'getOrders'])->add(new AuthMiddleware('vendor'));
$app->post('/api/vendor/{vendorId}/menu', [$vendorController, 'addMenuItem'])->add(new AuthMiddleware('vendor'));
$app->put('/api/menu-items/{id}', [$vendorController, 'updateMenuItem'])->add(new AuthMiddleware('vendor'));
$app->delete('/api/menu-items/{id}', [$vendorController, 'deleteMenuItem'])->add(new AuthMiddleware('vendor'));
$app->put('/api/menu-items/{id}/stock', [$vendorController, 'toggleStock'])->add(new AuthMiddleware('vendor'));
$app->put('/api/vendor/{id}/toggle-open', [$vendorController, 'toggleOpen'])->add(new AuthMiddleware('vendor'));