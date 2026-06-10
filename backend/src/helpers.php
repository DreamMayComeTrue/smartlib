<?php
/**
 * SmartLib — Shared helpers for writing JSON responses.
 *
 * Keeps every route consistent with the standard response shape:
 *   { status, code, message?, data? }
 */

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Write a JSON success response.
 *
 * @param Response             $rs
 * @param array<string,mixed>  $payload   Extra fields merged into the response
 * @param int                  $status    HTTP status code (default 200)
 */
function jsonSuccess(Response $rs, array $payload = [], int $status = 200): Response
{
    $body = array_merge(['status' => 'success', 'code' => $status], $payload);
    $rs->getBody()->write(json_encode($body));
    return $rs
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}

/**
 * Write a JSON error response.
 */
function jsonError(Response $rs, string $message, int $status = 400): Response
{
    $body = [
        'status'  => 'error',
        'code'    => $status,
        'message' => $message,
    ];
    $rs->getBody()->write(json_encode($body));
    return $rs
        ->withHeader('Content-Type', 'application/json')
        ->withStatus($status);
}
