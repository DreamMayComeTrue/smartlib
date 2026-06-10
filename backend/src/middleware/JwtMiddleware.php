<?php
/**
 * SmartLib — JWT authentication middleware.
 *
 * - Reads the `Authorization: Bearer <token>` header.
 * - Verifies the signature with the secret from .env.
 * - On success, attaches the decoded payload to the request as the `user` attribute.
 * - On failure, returns 401 (missing/invalid) or 403 (wrong role).
 *
 * Usage in routes.php:
 *   $app->post('/api/books', [BookController::class, 'create'])
 *       ->add(new JwtMiddleware('admin'));   // require admin role
 *
 *   $app->post('/api/borrow', $borrowHandler)
 *       ->add(new JwtMiddleware());          // any authenticated user
 */

declare(strict_types=1);

namespace SmartLib\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Slim\Psr7\Factory\ResponseFactory;
use Throwable;

class JwtMiddleware implements MiddlewareInterface
{
    /**
     * @param string|null $requiredRole 'admin' to restrict to admins, null for any logged-in user
     */
    public function __construct(private ?string $requiredRole = null)
    {
    }

    public function process(Request $request, Handler $handler): Response
    {
        $header = $request->getHeaderLine('Authorization');

        if (!$header || !preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return $this->respond(401, 'Missing or malformed Authorization header');
        }

        $token  = $matches[1];
        $secret = $_ENV['JWT_SECRET'] ?? '';
        $algo   = $_ENV['JWT_ALGO']   ?? 'HS256';

        if ($secret === '' || $secret === 'replace-me-with-a-long-random-string') {
            // Refuse to run with an unconfigured secret — fail closed
            return $this->respond(500, 'Server JWT secret is not configured');
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, $algo));
        } catch (Throwable $e) {
            return $this->respond(401, 'Invalid or expired token');
        }

        // Role check (if route demands a specific role)
        if ($this->requiredRole !== null && ($decoded->role ?? null) !== $this->requiredRole) {
            return $this->respond(403, 'Forbidden — insufficient privileges');
        }

        // Attach decoded payload to the request for downstream handlers
        $request = $request
            ->withAttribute('user', (array) $decoded)
            ->withAttribute('user_id', $decoded->id ?? null)
            ->withAttribute('user_role', $decoded->role ?? null);

        return $handler->handle($request);
    }

    private function respond(int $status, string $message): Response
    {
        $response = (new ResponseFactory())->createResponse($status);
        $response->getBody()->write(json_encode([
            'status'  => 'error',
            'code'    => $status,
            'message' => $message,
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
