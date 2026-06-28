<?php
declare(strict_types=1);

use App\Database;
use App\Controller\AdminController;
use App\Repositories\VendorRepository;
use App\Repositories\DisputeRepository;
use App\Repositories\OrderRepository;
use App\Middleware\AuthMiddleware;

global $app;

$db = Database::get();
$vendorRepo = new VendorRepository($db);
$disputeRepo = new DisputeRepository($db);
$orderRepo = new OrderRepository($db);

$adminController = new AdminController($vendorRepo, $disputeRepo, $orderRepo);
$adminOnly = new AuthMiddleware('admin');

$app->get('/api/admin/vendors', [$adminController, 'getVendors'])->add($adminOnly);
$app->put('/api/admin/vendors/{id}/approve', [$adminController, 'approveVendor'])->add($adminOnly);
$app->get('/api/admin/disputes', [$adminController, 'getDisputes'])->add($adminOnly);
$app->put('/api/disputes/{id}/resolve', [$adminController, 'resolveDispute'])->add($adminOnly);
$app->get('/api/admin/analytics', [$adminController , 'getAnalytics'])->add($adminOnly);