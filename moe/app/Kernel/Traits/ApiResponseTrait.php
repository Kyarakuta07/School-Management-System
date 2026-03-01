<?php

namespace App\Kernel\Traits;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * API Response Trait
 *
 * Provides consistent JSON response helpers for API controllers.
 * Ported from legacy moe/core/api_response.php.
 *
 * Usage: class MyApiController extends BaseController { use ApiResponseTrait; }
 */
trait ApiResponseTrait
{
    // ----- Error codes -----
    protected static string $ERR_UNAUTHORIZED = 'UNAUTHORIZED';
    protected static string $ERR_FORBIDDEN = 'FORBIDDEN';
    protected static string $ERR_NOT_FOUND = 'NOT_FOUND';
    protected static string $ERR_METHOD_NOT_ALLOWED = 'METHOD_NOT_ALLOWED';
    protected static string $ERR_RATE_LIMITED = 'RATE_LIMITED';
    protected static string $ERR_VALIDATION = 'VALIDATION_ERROR';
    protected static string $ERR_INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS';
    protected static string $ERR_DATABASE = 'DATABASE_ERROR';
    protected static string $ERR_INTERNAL = 'INTERNAL_ERROR';
    protected static string $ERR_DEAD_PET = 'PET_IS_DEAD';
    protected static string $ERR_NOT_OWNED = 'NOT_OWNED';

    /**
     * Return a success JSON response.
     */
    protected function apiSuccess($data = null, ?string $message = null, int $code = 200): ResponseInterface
    {
        $body = ['success' => true, 'timestamp' => time()];

        if ($message !== null) {
            $body['message'] = $message;
        }
        if ($data !== null) {
            $body['data'] = $data;
        }

        return $this->response->setStatusCode($code)->setJSON($body);
    }

    /**
     * Return an error JSON response.
     */
    protected function apiError(
        string $error,
        string $errorCode = 'INTERNAL_ERROR',
        int $httpCode = 400,
        $extraData = null
    ): ResponseInterface {
        $body = [
            'success' => false,
            'error' => $error,
            'error_code' => $errorCode,
            'timestamp' => time(),
        ];

        if ($extraData !== null) {
            $body['data'] = $extraData;
        }

        return $this->response->setStatusCode($httpCode)->setJSON($body);
    }

    // ── Convenience shortcuts ─────────────────────────────────

    protected function apiUnauthorized(string $msg = 'Unauthorized. Please login.'): ResponseInterface
    {
        return $this->apiError($msg, static::$ERR_UNAUTHORIZED, 401);
    }

    protected function apiForbidden(string $msg = 'Access forbidden.'): ResponseInterface
    {
        return $this->apiError($msg, static::$ERR_FORBIDDEN, 403);
    }

    protected function apiNotFound(string $msg = 'Resource not found.'): ResponseInterface
    {
        return $this->apiError($msg, static::$ERR_NOT_FOUND, 404);
    }

    protected function apiRateLimited(?string $lockedUntil = null): ResponseInterface
    {
        $extra = $lockedUntil ? ['wait_until' => $lockedUntil] : null;
        return $this->apiError('Rate limit exceeded. Please wait before trying again.', static::$ERR_RATE_LIMITED, 429, $extra);
    }

    protected function apiValidationError(string $msg): ResponseInterface
    {
        return $this->apiError($msg, static::$ERR_VALIDATION, 400);
    }

    protected function apiInsufficientFunds(int $required, int $current): ResponseInterface
    {
        return $this->apiError(
            "Not enough gold! Need {$required}, have {$current}.",
            static::$ERR_INSUFFICIENT_FUNDS,
            400,
            ['required' => $required, 'current' => $current]
        );
    }

    protected function apiDeadPetError(string $action = 'perform this action'): ResponseInterface
    {
        return $this->apiError("Cannot {$action} on a dead pet. Revive first!", static::$ERR_DEAD_PET, 400);
    }

    protected function apiNotOwned(string $resource = 'resource'): ResponseInterface
    {
        return $this->apiError("You do not own this {$resource}.", static::$ERR_NOT_OWNED, 403);
    }

    protected function apiDbError(string $internalMsg = 'Database operation failed'): ResponseInterface
    {
        log_message('error', "API DB Error: {$internalMsg}");
        return $this->apiError('A database error occurred. Please try again.', static::$ERR_DATABASE, 500);
    }
}
