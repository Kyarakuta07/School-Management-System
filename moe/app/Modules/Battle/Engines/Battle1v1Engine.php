<?php

namespace App\Modules\Battle\Engines;

/**
 * Battle1v1Engine
 * 
 * Logic for simulating 1v1 battles. Returns only simulation results.
 * MUST NOT interact with the database.
 */
class Battle1v1Engine
{
    /**
     * Simulate a 1v1 battle
     * 
     * @param array $attackerTeam Expected array with 1 pet
     * @param array $defenderTeam Expected array with 1 pet
     * @return array ['winner_id' => int, 'reward_gold' => int]
     */
    public function simulate(array $attackerTeam, array $defenderTeam): array
    {
        // Strict Validation: Exactly 1 pet per side
        if (count($attackerTeam) !== 1 || count($defenderTeam) !== 1) {
            throw new \InvalidArgumentException("1v1 Engine requires exactly 1 pet per side.");
        }

        $attacker = $attackerTeam[0];
        $defender = $defenderTeam[0];

        $p1Hp = 100 + ($attacker['level'] * 5);
        $p2Hp = 100 + ($defender['level'] * 5);
        $p1Speed = (int) ($attacker['base_speed'] ?? 10);
        $p2Speed = (int) ($defender['base_speed'] ?? 10);

        $turn = 0;
        $maxTurns = 50;

        while ($p1Hp > 0 && $p2Hp > 0 && $turn < $maxTurns) {
            $turn++;
            if ($p1Speed >= $p2Speed || mt_rand(1, 100) > 60) {
                $p2Hp -= $this->calculateDamage($attacker, $defender);
                if ($p2Hp <= 0)
                    break;
                $p1Hp -= $this->calculateDamage($defender, $attacker);
            } else {
                $p1Hp -= $this->calculateDamage($defender, $attacker);
                if ($p1Hp <= 0)
                    break;
                $p2Hp -= $this->calculateDamage($attacker, $defender);
            }
        }

        $playerWon = ($p1Hp > 0 && $p2Hp <= 0);
        $winnerId = $playerWon ? $attacker['id'] : $defender['id'];

        // Calculate gold reward (Engine only returns simulation results)
        $rewardGold = 0;
        if ($playerWon) {
            $baseGold = 5 + (int) ($defender['level'] * 1.5);
            $rewardGold = min(100, $baseGold);
        }

        return [
            'winner_id' => (int) $winnerId,
            'reward_gold' => (int) $rewardGold
        ];
    }

    protected function calculateDamage(array $attacker, array $defender): int
    {
        $atk = (int) ($attacker['base_attack'] ?? 10) + ($attacker['level'] * 1.5);
        $def = (int) ($defender['base_defense'] ?? 10) + ($defender['level'] * 1.2);
        $base = max(5, $atk - ($def * 0.5));

        // Simplified variance for simulation
        $variance = 0.85 + (mt_rand(0, 30) / 100);
        return (int) round($base * $variance);
    }
}
