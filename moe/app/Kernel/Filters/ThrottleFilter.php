<?php

namespace App\Kernel\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ThrottleFilter — API Rate Limiting
 *
 * Uses CI4's built-in Throttler to limit API requests per IP.
 * Default: 60 requests per minute.
 */
class ThrottleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $throttler = \Config\Services::throttler();

        // Allow 60 requests per minute per IP
        if (!$throttler->check(md5($request->getIPAddress()), 60, MINUTE)) {
            return \Config\Services::response()
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'error' => 'Too many requests. Please try again later.',
                    'code' => 'RATE_LIMIT_EXCEEDED',
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // No post-processing needed.
    }
}
