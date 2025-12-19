<?php
/**
 * Example Feature Test
 * 
 * Feature tests test complete user flows or API endpoints.
 */

namespace MOE\Tests\Feature;

use MOE\Tests\TestCase;

class ExampleFeatureTest extends TestCase
{
    /**
     * @test
     */
    public function example_feature_test(): void
    {
        // This is a placeholder test
        // Feature tests should test complete user flows
        $this->assertTrue(true);
    }

    /**
     * @test
     * @group api
     */
    public function api_endpoint_returns_json(): void
    {
        // Example: Test that API returns valid JSON
        // In a real test, you would make HTTP requests to your API

        $mockResponse = json_encode(['success' => true, 'data' => []]);
        $decoded = json_decode($mockResponse, true);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('success', $decoded);
    }
}
