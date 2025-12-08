<?php
/**
 * API Response Helper Functions
 * Mediterranean of Egypt - School Management System
 * 
 * Provides consistent JSON response formatting across all API endpoints.
 * 
 * Standard Response Format:
 * {
 *   "success": boolean,
 *   "message": string (optional),
 *   "data": mixed (optional),
 *   "error": string (only on failure),
 *   "error_code": string (only on failure),
 *   "timestamp": int (unix timestamp)
 * }
 */

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_METHOD_NOT_ALLOWED', 405);
define('HTTP_TOO_MANY_REQUESTS', 429);
define('HTTP_INTERNAL_ERROR', 500);

// Error Codes
define('ERR_UNAUTHORIZED', 'UNAUTHORIZED');
define('ERR_FORBIDDEN', 'FORBIDDEN');
define('ERR_NOT_FOUND', 'NOT_FOUND');
define('ERR_METHOD_NOT_ALLOWED', 'METHOD_NOT_ALLOWED');
define('ERR_RATE_LIMITED', 'RATE_LIMITED');
define('ERR_VALIDATION', 'VALIDATION_ERROR');
define('ERR_INSUFFICIENT_FUNDS', 'INSUFFICIENT_FUNDS');
define('ERR_DATABASE', 'DATABASE_ERROR');
define('ERR_INTERNAL', 'INTERNAL_ERROR');
define('ERR_DEAD_PET', 'PET_IS_DEAD');
define('ERR_NOT_OWNED', 'NOT_OWNED');

/**
 * Send a successful JSON response
 * 
 * @param mixed $data Data to include in response
 * @param string|null $message Optional success message
 * @param int $http_code HTTP status code (default 200)
 */
function api_success($data = null, $message = null, $http_code = HTTP_OK)
{
    http_response_code($http_code);

    $response = [
        'success' => true,
        'timestamp' => time()
    ];

    if ($message !== null) {
        $response['message'] = $message;
    }

    if ($data !== null) {
        $response['data'] = $data;
    }

    echo json_encode($response);
    exit();
}

/**
 * Send an error JSON response
 * 
 * @param string $error Human-readable error message
 * @param string $error_code Machine-readable error code
 * @param int $http_code HTTP status code (default 400)
 * @param mixed $extra_data Additional data to include
 */
function api_error($error, $error_code = ERR_INTERNAL, $http_code = HTTP_BAD_REQUEST, $extra_data = null)
{
    http_response_code($http_code);

    $response = [
        'success' => false,
        'error' => $error,
        'error_code' => $error_code,
        'timestamp' => time()
    ];

    if ($extra_data !== null) {
        $response['data'] = $extra_data;
    }

    echo json_encode($response);
    exit();
}

/**
 * Send unauthorized response
 */
function api_unauthorized($message = 'Unauthorized. Please login.')
{
    api_error($message, ERR_UNAUTHORIZED, HTTP_UNAUTHORIZED);
}

/**
 * Send forbidden response
 */
function api_forbidden($message = 'Access forbidden.')
{
    api_error($message, ERR_FORBIDDEN, HTTP_FORBIDDEN);
}

/**
 * Send not found response
 */
function api_not_found($message = 'Resource not found.')
{
    api_error($message, ERR_NOT_FOUND, HTTP_NOT_FOUND);
}

/**
 * Send method not allowed response
 * 
 * @param string $allowed Allowed HTTP method(s)
 */
function api_method_not_allowed($allowed = 'GET')
{
    header("Allow: $allowed");
    api_error("Method not allowed. Use $allowed.", ERR_METHOD_NOT_ALLOWED, HTTP_METHOD_NOT_ALLOWED);
}

/**
 * Send rate limited response
 * 
 * @param string|null $locked_until Time when limit resets
 */
function api_rate_limited($locked_until = null)
{
    $extra = null;
    if ($locked_until) {
        $extra = ['wait_until' => $locked_until];
    }
    api_error('Rate limit exceeded. Please wait before trying again.', ERR_RATE_LIMITED, HTTP_TOO_MANY_REQUESTS, $extra);
}

/**
 * Send validation error response
 * 
 * @param string $message Validation error message
 */
function api_validation_error($message)
{
    api_error($message, ERR_VALIDATION, HTTP_BAD_REQUEST);
}

/**
 * Send insufficient funds response
 * 
 * @param int $required Amount required
 * @param int $current Amount user has
 */
function api_insufficient_funds($required, $current)
{
    api_error(
        "Not enough gold! Need $required, have $current.",
        ERR_INSUFFICIENT_FUNDS,
        HTTP_BAD_REQUEST,
        ['required' => $required, 'current' => $current]
    );
}

/**
 * Send dead pet error response
 * 
 * @param string $action Action that cannot be performed
 */
function api_dead_pet_error($action = 'perform this action')
{
    api_error(
        "Cannot $action on a dead pet. Revive first!",
        ERR_DEAD_PET,
        HTTP_BAD_REQUEST
    );
}

/**
 * Send not owned error response
 * 
 * @param string $resource Type of resource (pet, item, etc.)
 */
function api_not_owned($resource = 'resource')
{
    api_error(
        "You do not own this $resource.",
        ERR_NOT_OWNED,
        HTTP_FORBIDDEN
    );
}

/**
 * Send database error response (logs internally, generic message to user)
 * 
 * @param string $internal_message Message to log
 */
function api_db_error($internal_message = 'Database operation failed')
{
    error_log("API DB Error: $internal_message");
    api_error('A database error occurred. Please try again.', ERR_DATABASE, HTTP_INTERNAL_ERROR);
}
