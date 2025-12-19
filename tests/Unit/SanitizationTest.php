<?php
/**
 * Unit tests for Sanitization functions
 */

namespace MOE\Tests\Unit;

use MOE\Tests\TestCase;

class SanitizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load the sanitization file
        require_once PROJECT_ROOT . '/moe/core/sanitization.php';
    }

    /**
     * @test
     */
    public function sanitize_string_removes_html_tags(): void
    {
        if (!function_exists('sanitize_string')) {
            $this->markTestSkipped('Function sanitize_string not found');
        }

        $input = '<script>alert("xss")</script>Hello';
        $result = sanitize_string($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('Hello', $result);
    }

    /**
     * @test
     */
    public function sanitize_string_trims_whitespace(): void
    {
        if (!function_exists('sanitize_string')) {
            $this->markTestSkipped('Function sanitize_string not found');
        }

        $input = '  Hello World  ';
        $result = sanitize_string($input);

        $this->assertEquals('Hello World', $result);
    }

    /**
     * @test
     */
    public function sanitize_email_returns_valid_email(): void
    {
        if (!function_exists('sanitize_email')) {
            $this->markTestSkipped('Function sanitize_email not found');
        }

        $input = 'test@example.com';
        $result = sanitize_email($input);

        $this->assertEquals('test@example.com', $result);
    }

    /**
     * @test
     */
    public function sanitize_email_removes_invalid_characters(): void
    {
        if (!function_exists('sanitize_email')) {
            $this->markTestSkipped('Function sanitize_email not found');
        }

        $input = 'test<>@example.com';
        $result = sanitize_email($input);

        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    /**
     * @test
     */
    public function sanitize_int_returns_integer(): void
    {
        if (!function_exists('sanitize_int')) {
            $this->markTestSkipped('Function sanitize_int not found');
        }

        $input = '123abc';
        $result = sanitize_int($input);

        $this->assertIsInt($result);
        $this->assertEquals(123, $result);
    }

    /**
     * @test
     */
    public function sanitize_int_handles_negative_numbers(): void
    {
        if (!function_exists('sanitize_int')) {
            $this->markTestSkipped('Function sanitize_int not found');
        }

        $input = '-456';
        $result = sanitize_int($input);

        $this->assertEquals(-456, $result);
    }
}
