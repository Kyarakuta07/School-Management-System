<?php

namespace Tests\Unit\Battle;

use CodeIgniter\Test\CIUnitTestCase;
use App\Modules\Battle\Engines\Battle3v3Engine;

/**
 * @internal
 */
final class Battle3v3EngineTest extends CIUnitTestCase
{
    private Battle3v3Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new Battle3v3Engine();
    }

    public function testSimulateAttackReturnsExpectedKeys(): void
    {
        $state = [
            'player_pets' => [['id' => 1], ['id' => 2], ['id' => 3]],
            'enemy_pets' => [['id' => 4], ['id' => 5], ['id' => 6]]
        ];
        $attacker = ['name' => 'Attacker', 'level' => 10, 'element' => 'Fire'];
        $skill = ['skill_name' => 'Fireball', 'base_damage' => 30, 'skill_element' => 'Fire'];
        $defender = ['name' => 'Defender', 'level' => 10, 'element' => 'Air'];

        $result = $this->engine->simulateAttack($state, $attacker, $skill, $defender);

        $this->assertArrayHasKey('damage_dealt', $result);
        $this->assertArrayHasKey('is_critical', $result);
        $this->assertArrayHasKey('element_advantage', $result);
        $this->assertArrayHasKey('logs', $result);
        $this->assertIsInt($result['damage_dealt']);
        $this->assertIsBool($result['is_critical']);
        $this->assertEquals('super_effective', $result['element_advantage']);
    }

    public function testSimulateRejectsInvalidTeamSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $state = [
            'player_pets' => [['id' => 1], ['id' => 2]], // Only 2
            'enemy_pets' => [['id' => 4], ['id' => 5], ['id' => 6]]
        ];
        $attacker = ['id' => 1];
        $skill = [];
        $defender = ['id' => 4];

        $this->engine->simulateAttack($state, $attacker, $skill, $defender);
    }

    public function testElementAdvantageFireBeatsAir(): void
    {
        $method = new \ReflectionMethod(Battle3v3Engine::class, 'getElementMultiplier');
        $method->setAccessible(true);

        $multiplier = $method->invoke($this->engine, 'Fire', 'Air');
        $this->assertEquals(2.0, $multiplier);
    }

    public function testElementDisadvantageFireVsWater(): void
    {
        $method = new \ReflectionMethod(Battle3v3Engine::class, 'getElementMultiplier');
        $method->setAccessible(true);

        $multiplier = $method->invoke($this->engine, 'Fire', 'Water');
        $this->assertEquals(0.5, $multiplier);
    }

    public function testSameElementIsNeutral(): void
    {
        $method = new \ReflectionMethod(Battle3v3Engine::class, 'getElementMultiplier');
        $method->setAccessible(true);

        $multiplier = $method->invoke($this->engine, 'Fire', 'Fire');
        $this->assertEquals(1.0, $multiplier);
    }

    public function testDamageAlwaysPositive(): void
    {
        // Extreme defense case
        $attacker = ['level' => 1];
        $skill = ['base_damage' => 1];
        $defender = ['level' => 100, 'element' => 'Fire'];

        $method = new \ReflectionMethod(Battle3v3Engine::class, 'calculateDetailedDamage');
        $method->setAccessible(true);

        $result = $method->invoke($this->engine, $attacker, $skill, $defender);

        $this->assertGreaterThanOrEqual(1, $result['damage_dealt']);
    }
}
