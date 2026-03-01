<?php

namespace App\Modules\Battle\Engines;

/**
 * Battle3v3Engine
 * 
 * Logic for simulating 3v3 battles. Returns only simulation results.
 * MUST NOT interact with the database.
 */
class Battle3v3Engine
{
    /**
     * Simulate a 3v3 battle turn.
     * 
     * @param array $state Current battle state (must contain 3 player_pets and 3 enemy_pets)
     * @param array $attacker The active attacking pet
     * @param array $skill Use this skill
     * @param array $defender The target pet
     */
    public function simulateAttack(array $state, array $attacker, array $skill, array $defender): array
    {
        // Strict Validation: Exactly 3 pets per side in the state
        if (count($state['player_pets'] ?? []) !== 3 || count($state['enemy_pets'] ?? []) !== 3) {
            throw new \InvalidArgumentException("3v3 Engine requires exactly 3 pets per side in the battle state.");
        }

        // Damage logic (pure calculation)
        $damageResult = $this->calculateDetailedDamage($attacker, $skill, $defender);

        // Return numeric outcomes, logs, and element advantage
        return [
            'damage_dealt' => $damageResult['damage_dealt'],
            'is_critical' => $damageResult['is_critical'],
            'element_advantage' => $damageResult['element_advantage'],
            'logs' => $damageResult['logs']
        ];
    }

    protected function calculateDetailedDamage(array $attacker, array $skill, array $defender): array
    {
        $baseDamage = $skill['base_damage'] ?? 25;
        $atkPower = 10 + (($attacker['level'] ?? 1) * 2);
        $defPower = 8 + (($defender['level'] ?? 1) * 1.5);
        $defenderElement = $defender['element'] ?? 'Fire';
        $attackerSkillElement = $skill['skill_element'] ?? $attacker['element'] ?? 'Fire';

        // Damage formula
        $damage = ($baseDamage + ($atkPower * 0.4)) * (100 / (100 + $defPower));

        // Element Multiplier
        $mult = $this->getElementMultiplier($attackerSkillElement, $defenderElement);
        $damage *= $mult;

        $advantage = 'neutral';
        if ($mult > 1)
            $advantage = 'super_effective';
        if ($mult < 1)
            $advantage = 'not_effective';

        // Crit (10%)
        $isCrit = mt_rand(1, 100) <= 10;
        if ($isCrit)
            $damage *= 1.5;

        // Variance
        $damage *= (0.9 + mt_rand(0, 20) / 100);
        $final = max(1, (int) round($damage));

        $attackerName = $attacker['name'] ?? 'Pet';
        $skillName = $skill['skill_name'] ?? 'Attack';
        $logMsg = "{$attackerName} used {$skillName} for {$final} damage!";

        if ($isCrit)
            $logMsg = "CRITICAL! " . $logMsg;
        if ($advantage === 'super_effective')
            $logMsg .= " (It's super effective!)";
        if ($advantage === 'not_effective')
            $logMsg .= " (It's not very effective...)";

        return [
            'damage_dealt' => $final,
            'is_critical' => $isCrit,
            'element_advantage' => $advantage,
            'logs' => [$logMsg]
        ];
    }

    protected function getElementMultiplier(string $a, string $d): float
    {
        if ($a === $d)
            return 1.0;

        $advantages = [
            'Fire' => 'Air',
            'Air' => 'Earth',
            'Earth' => 'Water',
            'Water' => 'Fire',
            'Light' => 'Dark',
            'Dark' => 'Light',
        ];

        if (isset($advantages[$a]) && $advantages[$a] === $d)
            return 2.0;
        if (isset($advantages[$d]) && $advantages[$d] === $a)
            return 0.5;

        return 1.0;
    }
}
