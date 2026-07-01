<?php
declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';
date_default_timezone_set('UTC');
// The config/db.php inclusion is dropped completely as autoload resolves App\Database via PSR-4 singleton setup maps.
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
// CORS Settings Middleware
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
});

$app->add(new App\Middleware\SecurityHeaders());
$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$debug = filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOLEAN);
$app->addErrorMiddleware($debug, $debug, $debug);

$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok', 'service' => 'CPAD Project Ascent API']));
    return $response->withHeader('Content-Type', 'application/json');
});

// Include Decoupled Architecture Routes
require __DIR__ . '/../src/Routes/auth.php';
require __DIR__ . '/../src/Routes/student.php';
require __DIR__ . '/../src/Routes/vendor.php';
require __DIR__ . '/../src/Routes/admin.php';
require __DIR__ . '/../src/Routes/notifications.php';

$app->run();