<?php

namespace Tests\Unit\Battle;

use CodeIgniter\Test\CIUnitTestCase;
use App\Modules\Battle\Engines\Battle1v1Engine;

/**
 * @internal
 */
final class Battle1v1EngineTest extends CIUnitTestCase
{
    private Battle1v1Engine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new Battle1v1Engine();
    }

    public function testSimulateReturnsWinnerAndGold(): void
    {
        $attackerTeam = [['id' => 1, 'level' => 10, 'base_attack' => 15, 'base_defense' => 10, 'base_speed' => 12]];
        $defenderTeam = [['id' => 2, 'level' => 10, 'base_attack' => 15, 'base_defense' => 10, 'base_speed' => 12]];

        $result = $this->engine->simulate($attackerTeam, $defenderTeam);

        $this->assertArrayHasKey('winner_id', $result);
        $this->assertArrayHasKey('reward_gold', $result);
        $this->assertContains($result['winner_id'], [1, 2]);
        $this->assertGreaterThanOrEqual(0, $result['reward_gold']);
    }

    public function testSimulateHigherLevelPetAdvantage(): void
    {
        // One pet is significantly stronger
        $attackerTeam = [['id' => 1, 'level' => 100, 'base_attack' => 50, 'base_defense' => 50, 'base_speed' => 50]];
        $defenderTeam = [['id' => 2, 'level' => 1, 'base_attack' => 5, 'base_defense' => 5, 'base_speed' => 5]];

        // Run multiple times to reduce randomness impact
        $attackerWins = 0;
        for ($i = 0; $i < 10; $i++) {
            $result = $this->engine->simulate($attackerTeam, $defenderTeam);
            if ($result['winner_id'] === 1) $attackerWins++;
        }

        $this->assertGreaterThan(5, $attackerWins, "Stronger pet should win most of the time");
    }

    public function testSimulateRejectsInvalidTeamSize(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("1v1 Engine requires exactly 1 pet per side.");

        $attackerTeam = [['id' => 1], ['id' => 2]];
        $defenderTeam = [['id' => 3]];

        $this->engine->simulate($attackerTeam, $defenderTeam);
    }

    public function testGoldRewardCappedAt100(): void
    {
        // High level defender to trigger max gold
        $attackerTeam = [['id' => 1, 'level' => 100, 'base_attack' => 1000, 'base_defense' => 1000, 'base_speed' => 1000]];
        $defenderTeam = [['id' => 2, 'level' => 200, 'base_attack' => 1, 'base_defense' => 1, 'base_speed' => 1]];

        $result = $this->engine->simulate($attackerTeam, $defenderTeam);

        if ($result['winner_id'] === 1) {
            $this->assertLessThanOrEqual(100, $result['reward_gold']);
        }
    }

    public function testCalculateDamageNeverZero(): void
    {
        // Case: very high defense vs low attack
        $attacker = ['level' => 1, 'base_attack' => 1];
        $defender = ['level' => 100, 'base_defense' => 1000];

        // Access protected method via Reflection for unit testing internal logic
        $method = new \ReflectionMethod(Battle1v1Engine::class, 'calculateDamage');
        $method->setAccessible(true);

        $damage = $method->invoke($this->engine, $attacker, $defender);

        $this->assertGreaterThanOrEqual(5, $damage, "Minimal damage should be 5");
    }
}
