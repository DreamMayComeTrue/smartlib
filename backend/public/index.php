<?php
/**
 * SmartLib Backend — Slim 4 entry point.
 *
 * All HTTP requests are routed here by .htaccess and dispatched to
 * the route handlers in src/routes.php.
 */

declare(strict_types=1);

use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;

require __DIR__ . '/../vendor/autoload.php';

// ── 1. Load environment variables from .env ──────────────────────────────
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// ── 2. Database connection (singleton) ───────────────────────────────────
require __DIR__ . '/../src/db.php';

// ── 3. Build Slim app ────────────────────────────────────────────────────
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Custom error handler — returns JSON, never HTML
$errorMiddleware = $app->addErrorMiddleware(
    displayErrorDetails: (($_ENV['APP_DEBUG'] ?? 'false') === 'true'),
    logErrors: true,
    logErrorDetails: true,
);
$errorMiddleware->setDefaultErrorHandler(function (
    Request $request,
    Throwable $exception,
    bool $displayErrorDetails
) use ($app): Response {
    $response = $app->getResponseFactory()->createResponse();
    $payload  = [
        'status'  => 'error',
        'code'    => 500,
        'message' => $displayErrorDetails ? $exception->getMessage() : 'Internal server error',
    ];
    $response->getBody()->write(json_encode($payload));
    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(500);
});

// ── 4. CORS middleware ───────────────────────────────────────────────────
// Explicitly allow ONLY the configured frontend origin — never use '*' in prod.
$app->add(function (Request $request, Handler $handler): Response {
    $origin = $_ENV['CORS_ORIGIN'] ?? 'http://localhost:5173';
    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $origin)
        ->withHeader('Access-Control-Allow-Headers', 'Authorization, Content-Type')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true')
        ->withHeader('Vary', 'Origin');
});

// Handle CORS pre-flight (OPTIONS) for every route
$app->options('/{routes:.+}', function (Request $request, Response $response): Response {
    return $response;
});

// ── 5. Register routes ───────────────────────────────────────────────────
require __DIR__ . '/../src/routes.php';

$app->run();
