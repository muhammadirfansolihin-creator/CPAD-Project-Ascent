<?php
declare(strict_types=1);

use App\Database;
use App\Controller\StudentController;
use App\Repositories\VendorRepository;
use App\Repositories\OrderRepository;
use App\Repositories\DisputeRepository;
use App\Middleware\AuthMiddleware;

global $app;

$db = Database::get();
$vendorRepo = new VendorRepository($db);
$orderRepo = new OrderRepository($db);
$disputeRepo = new DisputeRepository($db);

$studentController = new StudentController($vendorRepo, $orderRepo, $disputeRepo);

$app->get('/api/vendors', [$studentController, 'getVendors'])->add(new AuthMiddleware());
$app->get('/api/vendors/{id}', [$studentController, 'getVendorDetails'])->add(new AuthMiddleware());
$app->get('/api/vendors/{id}/menu', [$studentController, 'getVendorMenu'])->add(new AuthMiddleware());
$app->get('/api/orders', [$studentController, 'getOrders'])->add(new AuthMiddleware());
$app->post('/api/orders', [$studentController, 'createOrder'])->add(new AuthMiddleware());
$app->post('/api/disputes', [$studentController, 'fileDispute'])->add(new AuthMiddleware());
$app->post('/api/reviews', [$studentController, 'submitReview'])->add(new AuthMiddleware());
$app->get('/api/profile/reviews', [$studentController, 'getReviews'])->add(new AuthMiddleware());
$app->get('/api/vendors/{id}/reviews', [$studentController, 'getVendorReviews'])->add(new AuthMiddleware());
$app->get('/api/active-banner', [$studentController, 'getActiveBanner'])->add(new AuthMiddleware());