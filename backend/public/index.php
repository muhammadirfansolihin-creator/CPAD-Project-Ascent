<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../config/db.php';

$app = AppFactory::create();

// Add body parsing and routing middleware
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// CORS headers
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

// Preflight OPTIONS
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

// Error middleware (last, so it wraps everything)
$app->addErrorMiddleware(true, true, true);

// Routes
require __DIR__ . '/../src/Routes/auth.php';
require __DIR__ . '/../src/Routes/student.php';
require __DIR__ . '/../src/Routes/vendor.php';
require __DIR__ . '/../src/Routes/admin.php';
require __DIR__ . '/../src/Routes/notifications.php';

$app->run();
