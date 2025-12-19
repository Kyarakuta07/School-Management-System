<?php
/**
 * Unit tests for CSRF protection
 */

namespace MOE\Tests\Unit;

use MOE\Tests\TestCase;

class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Start session for CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Load the CSRF file
        require_once PROJECT_ROOT . '/moe/core/csrf.php';
    }

    /**
     * @test
     */
    public function generate_csrf_token_creates_token(): void
    {
        if (!function_exists('generate_csrf_token')) {
            $this->markTestSkipped('Function generate_csrf_token not found');
        }

        $token = generate_csrf_token();

        $this->assertNotEmpty($token);
        $this->assertIsString($token);
    }

    /**
     * @test
     */
    public function generate_csrf_token_stores_in_session(): void
    {
        if (!function_exists('generate_csrf_token')) {
            $this->markTestSkipped('Function generate_csrf_token not found');
        }

        $token = generate_csrf_token();

        $this->assertArrayHasKey('csrf_token', $_SESSION);
        $this->assertEquals($token, $_SESSION['csrf_token']);
    }

    /**
     * @test
     */
    public function verify_csrf_token_validates_correct_token(): void
    {
        if (!function_exists('verify_csrf_token') || !function_exists('generate_csrf_token')) {
            $this->markTestSkipped('CSRF functions not found');
        }

        $token = generate_csrf_token();
        $result = verify_csrf_token($token);

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function verify_csrf_token_rejects_invalid_token(): void
    {
        if (!function_exists('verify_csrf_token') || !function_exists('generate_csrf_token')) {
            $this->markTestSkipped('CSRF functions not found');
        }

        generate_csrf_token();
        $result = verify_csrf_token('invalid_token');

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function verify_csrf_token_rejects_empty_token(): void
    {
        if (!function_exists('verify_csrf_token')) {
            $this->markTestSkipped('Function verify_csrf_token not found');
        }

        $result = verify_csrf_token('');

        $this->assertFalse($result);
    }
}
