<?php
/**
 * Base TestCase for all unit tests
 * 
 * Provides common utilities and setup for testing.
 */

namespace MOE\Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up before each test
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Reset session for each test
        $_SESSION = [];
    }

    /**
     * Tear down after each test
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        // Clear any mocks
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
    }

    /**
     * Create a mock database connection
     * 
     * @return \mysqli|MockObject
     */
    protected function createMockConnection()
    {
        return $this->createMock(\mysqli::class);
    }

    /**
     * Assert that a response is successful JSON
     * 
     * @param array $response The response array
     */
    protected function assertSuccessResponse(array $response): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertTrue($response['success']);
    }

    /**
     * Assert that a response is an error
     * 
     * @param array $response The response array
     * @param string|null $expectedError Optional expected error message
     */
    protected function assertErrorResponse(array $response, ?string $expectedError = null): void
    {
        $this->assertArrayHasKey('success', $response);
        $this->assertFalse($response['success']);

        if ($expectedError !== null) {
            $this->assertArrayHasKey('error', $response);
            $this->assertEquals($expectedError, $response['error']);
        }
    }
}
