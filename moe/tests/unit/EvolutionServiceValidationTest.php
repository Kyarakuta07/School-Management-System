<?php

namespace Tests\Unit\Pet;

use CodeIgniter\Test\CIUnitTestCase;
use App\Modules\Pet\Services\EvolutionService;

/**
 * @internal
 */
final class EvolutionServiceValidationTest extends CIUnitTestCase
{
    private EvolutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock DB connection just for instantiation, but we won't use it
        $db = $this->createMock(\CodeIgniter\Database\ConnectionInterface::class);
        $this->service = new EvolutionService($db);
    }

    private function callValidate(string $stage, int $level): array
    {
        $method = new \ReflectionMethod(EvolutionService::class, 'validateEvolutionRequirements');
        $method->setAccessible(true);
        return $method->invoke($this->service, $stage, $level);
    }

    public function testEggCanEvolveAtLevel30(): void
    {
        $result = $this->callValidate('egg', 30);
        $this->assertTrue($result['success']);
        $this->assertEquals('baby', $result['next_stage']);
    }

    public function testEggCannotEvolveUnderLevel30(): void
    {
        $result = $this->callValidate('egg', 29);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Level 30+', $result['error']);
    }

    public function testBabyCanEvolveAtLevel70(): void
    {
        $result = $this->callValidate('baby', 70);
        $this->assertTrue($result['success']);
        $this->assertEquals('adult', $result['next_stage']);
    }

    public function testBabyCannotEvolveUnderLevel70(): void
    {
        $result = $this->callValidate('baby', 69);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Level 70+', $result['error']);
    }

    public function testAdultCannotEvolve(): void
    {
        $result = $this->callValidate('adult', 100);
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('max evolution', $result['error']);
    }
}
