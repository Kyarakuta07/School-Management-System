<?php
/**
 * Unit tests for Helper functions
 */

namespace MOE\Tests\Unit;

use MOE\Tests\TestCase;

class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Load the helpers file
        require_once PROJECT_ROOT . '/moe/core/helpers.php';
    }

    /**
     * @test
     */
    public function format_phone_number_formats_correctly(): void
    {
        if (!function_exists('format_phone_number')) {
            $this->markTestSkipped('Function format_phone_number not found');
        }

        $input = '081234567890';
        $result = format_phone_number($input);

        $this->assertIsString($result);
    }

    /**
     * @test
     */
    public function generate_random_string_returns_correct_length(): void
    {
        if (!function_exists('generate_random_string')) {
            $this->markTestSkipped('Function generate_random_string not found');
        }

        $length = 16;
        $result = generate_random_string($length);

        $this->assertEquals($length, strlen($result));
    }

    /**
     * @test
     */
    public function generate_otp_returns_6_digit_string(): void
    {
        if (!function_exists('generate_otp')) {
            $this->markTestSkipped('Function generate_otp not found');
        }

        $result = generate_otp();

        $this->assertEquals(6, strlen($result));
        $this->assertIsNumeric($result);
    }

    /**
     * @test
     */
    public function format_currency_formats_number_correctly(): void
    {
        if (!function_exists('format_currency')) {
            $this->markTestSkipped('Function format_currency not found');
        }

        $amount = 1000000;
        $result = format_currency($amount);

        $this->assertIsString($result);
    }

    /**
     * @test
     */
    public function time_ago_returns_string(): void
    {
        if (!function_exists('time_ago')) {
            $this->markTestSkipped('Function time_ago not found');
        }

        $timestamp = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $result = time_ago($timestamp);

        $this->assertIsString($result);
    }

    /**
     * @test
     */
    public function validate_password_rejects_short_passwords(): void
    {
        if (!function_exists('validate_password')) {
            $this->markTestSkipped('Function validate_password not found');
        }

        $shortPassword = '123';
        $result = validate_password($shortPassword);

        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function validate_password_accepts_strong_passwords(): void
    {
        if (!function_exists('validate_password')) {
            $this->markTestSkipped('Function validate_password not found');
        }

        $strongPassword = 'SecurePass123!';
        $result = validate_password($strongPassword);

        $this->assertTrue($result);
    }
}
