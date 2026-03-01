<?php
/**
 * CodeIgniter 4 IDE Helper — Stubs for global functions and constants.
 *
 * This file is NOT loaded at runtime. It exists solely to provide
 * type information to static analysis tools (Intelephense, PHPStan, etc.)
 * so they can resolve CI4's global helper functions and constants.
 *
 * @see system/Common.php  — where these functions are actually defined.
 */

// ─── CI4 CLASS STUBS ─────────────────────────────────────────────────

namespace CodeIgniter\HTTP {

    class Request
    {
    }

    /**
     * Represents an incoming HTTP request.
     */
    class IncomingRequest extends Request
    {
        /** @return \CodeIgniter\HTTP\Files\UploadedFile|null */
        public function getFile(string $fileID)
        {
            return null;
        }
        /** @return mixed */
        public function getGet($index = null, $filter = null, $flags = null)
        {
            return null;
        }
        /** @return mixed */
        public function getPost($index = null, $filter = null, $flags = null)
        {
            return null;
        }
        /** @return mixed */
        public function getJSON(bool $assoc = false, int $depth = 512, int $options = 0)
        {
            return null;
        }
        public function getMethod(bool $upper = false): string
        {
            return '';
        }
    }

    class RedirectResponse
    {
        /** @return static */
        public function to(string $uri, int $code = 302, string $method = 'auto')
        {
            return $this;
        }
        /** @return static */
        public function route(string $route, ...$params)
        {
            return $this;
        }
        /** @return static */
        public function back(int $code = 302, string $method = 'auto')
        {
            return $this;
        }
        /** @return static */
        public function with(string $key, $message)
        {
            return $this;
        }
    }

    class URI
    {
    }
    class UserAgent
    {
    }
}

namespace CodeIgniter\HTTP\Files {
    class UploadedFile
    {
        public function isValid(): bool
        {
            return true;
        }
        public function getMimeType(): string
        {
            return '';
        }
        public function getName(): string
        {
            return '';
        }
        public function getRandomName(): string
        {
            return '';
        }
        public function getClientName(): string
        {
            return '';
        }
        public function getSize(): int
        {
            return 0;
        }
        public function move(string $targetPath, ?string $name = null): bool
        {
            return true;
        }
    }
}

namespace CodeIgniter\Cache {
    interface CacheInterface
    {
        /**
         * @param string $key
         * @param mixed  $value
         * @param int    $ttl
         */
        public function save(string $key, $value, int $ttl = 60): bool;
        /** @return mixed */
        public function get(string $key);
        public function delete(string $key): bool;
        public function clean(): bool;
    }
}

namespace CodeIgniter\Session {
    class Session
    {
        /** @return mixed */
        public function get(?string $key = null)
        {
            return null;
        }
        /** @param string|array $data */
        public function set($data, $value = null): void
        {
        }
        /** @return mixed */
        public function getFlashdata(?string $key = null)
        {
            return null;
        }
        /** @param mixed $value */
        public function setFlashdata(string $key, $value): void
        {
        }
        public function destroy(): void
        {
        }
    }
}

namespace CodeIgniter\Debug {
    class Timer
    {
        /** @return static */
        public function start(string $name, ?float $time = null)
        {
            return $this;
        }
        /** @return static */
        public function stop(string $name)
        {
            return $this;
        }
        public function getElapsedTime(string $name, int $decimals = 4): float
        {
            return 0.0;
        }
    }
}

// ─── GLOBAL SCOPE ────────────────────────────────────────────────────

namespace {

    // ==================================================================
    // GLOBAL HELPER FUNCTIONS (from system/Common.php)
    // ==================================================================

    if (!function_exists('session')) {
        /**
         * @param string|null $key
         * @return \CodeIgniter\Session\Session|mixed
         */
        function session(?string $key = null)
        {
            return new \CodeIgniter\Session\Session();
        }
    }

    if (!function_exists('service')) {
        /** @return mixed */
        function service(string $name, ...$params)
        {
            return null;
        }
    }

    if (!function_exists('log_message')) {
        /** @return void */
        function log_message(string $level, string $message, array $context = [])
        {
        }
    }

    if (!function_exists('helper')) {
        /**
         * @param string|array $filenames
         * @return void
         */
        function helper($filenames)
        {
        }
    }

    if (!function_exists('esc')) {
        /**
         * @param mixed $data
         * @return mixed
         */
        function esc($data, string $context = 'html', ?string $encoding = null)
        {
            return $data;
        }
    }

    if (!function_exists('redirect')) {
        /** @return \CodeIgniter\HTTP\RedirectResponse */
        function redirect(?string $route = null)
        {
            return new \CodeIgniter\HTTP\RedirectResponse();
        }
    }

    if (!function_exists('base_url')) {
        function base_url(string $relativePath = ''): string
        {
            return '';
        }
    }

    if (!function_exists('view')) {
        function view(string $name, array $data = [], array $options = []): string
        {
            return '';
        }
    }

    if (!function_exists('old')) {
        /** @return mixed */
        function old(string $key, $default = null)
        {
            return $default;
        }
    }

    if (!function_exists('csrf_token')) {
        function csrf_token(): string
        {
            return '';
        }
    }

    if (!function_exists('csrf_hash')) {
        function csrf_hash(): string
        {
            return '';
        }
    }

    if (!function_exists('csrf_field')) {
        function csrf_field(): string
        {
            return '';
        }
    }

    if (!function_exists('config')) {
        /** @return mixed */
        function config(string $name)
        {
            return null;
        }
    }

    if (!function_exists('validation_errors')) {
        function validation_errors(): string
        {
            return '';
        }
    }

    if (!function_exists('site_url')) {
        function site_url(string $relativePath = ''): string
        {
            return '';
        }
    }

    if (!function_exists('current_url')) {
        function current_url(): string
        {
            return '';
        }
    }

    if (!function_exists('cache')) {
        /** @return \CodeIgniter\Cache\CacheInterface|mixed */
        function cache(?string $key = null)
        {
            return null;
        }
    }

    if (!function_exists('is_cli')) {
        function is_cli(): bool
        {
            return false;
        }
    }

    if (!function_exists('wrap_plain_text')) {
        function wrap_plain_text(string $text): string
        {
            return '';
        }
    }

    // ==================================================================
    // ROLE CONSTANTS (from app/Config/Constants.php)
    // ==================================================================

    define('ROLE_NETHERA', 'Nethera');
    define('ROLE_VASIKI', 'Vasiki');
    define('ROLE_HAKAES', 'Hakaes');
    define('ROLE_ANUBIS', 'Anubis');

    // ==================================================================
    // CI4 PATH CONSTANTS
    // ==================================================================

    define('APPPATH', '/app/');
    define('ROOTPATH', '/');
    define('SYSTEMPATH', '/system/');
    define('WRITEPATH', '/writable/');
    define('FCPATH', '/public/');
}
