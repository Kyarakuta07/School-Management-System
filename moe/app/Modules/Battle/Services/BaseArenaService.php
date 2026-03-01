<?php

namespace App\Modules\Battle\Services;

use CodeIgniter\Database\BaseConnection;
use App\Modules\Battle\Models\BattleModel;
use App\Modules\Pet\Interfaces\PetServiceInterface;
use App\Modules\User\Interfaces\GoldServiceInterface;

/**
 * BaseArenaService
 * 
 * Shared logic for all Arena modes (1v1, 3v3, etc.)
 */
class BaseArenaService
{
    protected BaseConnection $db;
    protected BattleModel $battleModel;
    protected PetServiceInterface $petService;
    protected GoldServiceInterface $goldService;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->battleModel = new BattleModel();
        $this->petService = service('petService');
        $this->goldService = service('goldService');
    }

    public function getElementMultiplier(string $a, string $d): float
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

        if (isset($advantages[$a]) && $advantages[$a] === $d) {
            return 2.0; // Super effective
        }
        if (isset($advantages[$d]) && $advantages[$d] === $a) {
            return 0.5; // Not effective
        }

        return 1.0;
    }

    public function calculateDetailedDamage(array $attacker, array $skill, ?array $defender): array
    {
        $logs = [];
        $baseDamage = $skill['base_damage'] ?? 25;
        $atkPower = 10 + (($attacker['level'] ?? 1) * 2);

        // Handle cases where defender might be null (e.g. wild battles with issues)
        $defLevel = $defender['level'] ?? 1;
        $defPower = 8 + ($defLevel * 1.5);
        $defenderElement = $defender['element'] ?? 'Normalized';

        // Raw damage formula
        $damage = ($baseDamage + ($atkPower * 0.4)) * (100 / (100 + $defPower));

        // Use the comprehensive multiplier
        $mult = $this->getElementMultiplier($skill['skill_element'] ?? $attacker['element'] ?? 'Normalized', $defenderElement);
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

        $attackerName = $attacker['name'] ?? $attacker['nickname'] ?? $attacker['species_name'] ?? 'Pet';
        $skillName = $skill['skill_name'] ?? 'Attack';
        $logs[] = "{$attackerName} used {$skillName} for {$final} damage!";

        // Status Effect Processing
        $statusApplied = false;
        $statusEffect = null;
        $statusResisted = false;

        $skillStatusEffect = $skill['status_effect'] ?? null;
        $skillStatusChance = (int) ($skill['status_chance'] ?? 0);

        if ($skillStatusEffect && $skillStatusChance > 0) {
            // Roll for status chance
            $roll = mt_rand(1, 100);
            if ($roll <= $skillStatusChance) {
                // Check defender's element resistance
                $resistance = $this->getStatusResistance($defenderElement, $skillStatusEffect);
                $resistRoll = mt_rand(1, 100);

                if ($resistRoll <= $resistance) {
                    // Resisted!
                    $statusResisted = true;
                    $defenderName = $defender['name'] ?? $defender['nickname'] ?? $defender['species_name'] ?? 'Defender';
                    $logs[] = "{$defenderName} resisted {$skillStatusEffect}! ({$defenderElement} resistance)";
                } else {
                    // Status applied!
                    $statusApplied = true;
                    $statusEffect = $skillStatusEffect;
                    $statusLabels = [
                        'burn' => '🔥 Burn',
                        'freeze' => '❄️ Freeze',
                        'stun' => '⚡ Stun',
                        'poison' => '☠️ Poison',
                        'atk_down' => '⬇️ ATK Down',
                        'def_down' => '🛡️ DEF Down',
                    ];
                    $label = $statusLabels[$skillStatusEffect] ?? ucfirst($skillStatusEffect);
                    $defenderName = $defender['name'] ?? $defender['nickname'] ?? $defender['species_name'] ?? 'Defender';
                    $logs[] = "{$defenderName} is inflicted with {$label}!";
                }
            }
        }

        return [
            'damage_dealt' => $final,
            'is_critical' => $isCrit,
            'element_advantage' => $advantage,
            'status_applied' => $statusApplied,
            'status_effect' => $statusEffect,
            'status_duration' => (int) ($skill['status_duration'] ?? 0),
            'status_resisted' => $statusResisted,
            'logs' => $logs
        ];
    }

    /**
     * Get status effect resistance percentage for an element.
     * Cached for 5 minutes.
     */
    public function getStatusResistance(string $element, string $statusEffect): int
    {
        $cacheKey = 'element_status_resistance_map';
        $map = cache()->get($cacheKey);

        if (!is_array($map)) {
            $rows = $this->db->table('element_status_resistance')->get()->getResultArray();
            $map = [];
            foreach ($rows as $row) {
                $key = $row['element'] . ':' . $row['resists_status'];
                $map[$key] = (int) $row['resistance_percent'];
            }
            cache()->save($cacheKey, $map, 300);
        }

        return $map["{$element}:{$statusEffect}"] ?? 0;
    }

    public function getPetForBattle(int $petId, ?int $userId = null): ?array
    {
        $builder = $this->db->table('user_pets AS up')
            ->select('up.*, ps.name AS species_name, ps.element, ps.base_attack, ps.base_defense, ps.base_speed, ps.rarity, ps.img_egg, ps.img_baby, ps.img_adult')
            ->join('pet_species AS ps', 'ps.id = up.species_id')
            ->where('up.id', $petId)
            ->where('up.status', 'ALIVE');

        if ($userId !== null) {
            $builder->where('up.user_id', $userId);
        }

        return $builder->get()->getRowArray();
    }

    public function getPetSkills(int $speciesId): array
    {
        $skills = $this->db->table('pet_skills')
            ->where('species_id', $speciesId)
            ->orderBy('skill_slot')
            ->get()
            ->getResultArray();

        if (empty($skills)) {
            $species = $this->db->table('pet_species')->select('element')->where('id', $speciesId)->get()->getRowArray();
            $element = $species['element'] ?? 'Fire';
            return [
                ['id' => 0, 'skill_name' => 'Basic Attack', 'base_damage' => 25, 'skill_element' => $element, 'skill_slot' => 1],
                ['id' => 0, 'skill_name' => 'Power Strike', 'base_damage' => 40, 'skill_element' => $element, 'skill_slot' => 2],
                ['id' => 0, 'skill_name' => 'Special Attack', 'base_damage' => 60, 'skill_element' => $element, 'skill_slot' => 3],
                ['id' => 0, 'skill_name' => 'Ultimate', 'base_damage' => 80, 'skill_element' => $element, 'skill_slot' => 4],
            ];
        }

        return $skills;
    }

    public function getSkill(int $skillId, array $attacker): array
    {
        if ($skillId <= 0) {
            return [
                'id' => 0,
                'skill_name' => 'Basic Attack',
                'base_damage' => 25,
                'skill_element' => $attacker['element'] ?? 'Fire'
            ];
        }

        $skillModel = new \App\Modules\Pet\Models\SkillModel();
        $skill = $skillModel->find($skillId);

        // Validate skill belongs to the attacker's species
        if (!$skill || (isset($skill['species_id']) && isset($attacker['species_id']) && (int) $skill['species_id'] !== (int) $attacker['species_id'])) {
            return [
                'id' => 0,
                'skill_name' => 'Basic Attack',
                'base_damage' => 25,
                'skill_element' => $attacker['element'] ?? 'Fire'
            ];
        }

        return $skill;
    }

    public function getAiSkillSelection(array $attacker, array $defender, bool $hardMode = false): array
    {
        $skills = $this->getPetSkills($attacker['species_id']);

        if ($hardMode && count($skills) > 1) {
            $bestSkill = $skills[0];
            $bestMult = -1.0;

            foreach ($skills as $s) {
                $m = $this->getElementMultiplier($s['skill_element'] ?? $attacker['element'], $defender['element'] ?? 'Fire');
                if ($m > $bestMult) {
                    $bestMult = $m;
                    $bestSkill = $s;
                }
            }
            return $bestSkill;
        }

        return $skills[array_rand($skills)];
    }

    public function formatPetForArena(array $pet): array
    {
        $hp = 100 + ($pet['level'] * 5);
        return array_merge($pet, [
            'max_hp' => $hp,
            'hp' => $hp,
            'is_fainted' => false,
            'name' => $pet['nickname'] ?: ($pet['species_name'] ?? 'Wild Pet')
        ]);
    }

    protected function generateAIOpponents(int $count, int $avgLevel): array
    {
        $enemies = [];

        // Count + random offsets instead of ORDER BY RAND()
        $totalSpecies = $this->db->table('pet_species')->countAllResults();
        if ($totalSpecies === 0)
            return [];

        $offsets = [];
        $pick = min($count, $totalSpecies);
        while (count($offsets) < $pick) {
            $offsets[mt_rand(0, $totalSpecies - 1)] = true;
        }

        foreach (array_keys($offsets) as $offset) {
            $s = $this->db->table('pet_species')->limit(1, $offset)->get()->getRowArray();
            if (!$s)
                continue;
            $enemies[] = [
                'id' => -1,
                'name' => 'Wild ' . $s['name'],
                'level' => $avgLevel,
                'hp' => 100 + ($avgLevel * 5),
                'max_hp' => 100 + ($avgLevel * 5),
                'atk' => (int) ($s['base_attack'] ?? 50) + ($avgLevel * 2),
                'def' => (int) ($s['base_defense'] ?? 50) + (int) ($avgLevel * 1.5),
                'element' => $s['element'],
                'species_id' => $s['id'],
                'species_name' => $s['name'],
                'evolution_stage' => ($avgLevel >= 20) ? 'adult' : (($avgLevel >= 10) ? 'baby' : 'egg'),
                'img_egg' => $s['img_egg'],
                'img_baby' => $s['img_baby'],
                'img_adult' => $s['img_adult'],
                'is_fainted' => false,
                'is_ai' => true
            ];
        }
        return $enemies;
    }

    protected function updateUserArenaStats(int $userId, int $petId, bool $isWin, int $eloChange): int
    {
        // 1. Atomic update of user stats (no read-modify-write race)
        if ($isWin) {
            $this->db->query(
                "UPDATE nethera SET arena_wins = arena_wins + 1, current_win_streak = current_win_streak + 1 WHERE id_nethera = ?",
                [$userId]
            );
        } else {
            $this->db->query(
                "UPDATE nethera SET arena_losses = arena_losses + 1, current_win_streak = 0 WHERE id_nethera = ?",
                [$userId]
            );
        }

        // 2. Update Pet RP with row-level lock
        $newRp = 1000;
        if ($petId > 0) {
            $pet = $this->db->query("SELECT rank_points FROM user_pets WHERE id = ? FOR UPDATE", [$petId])->getRowArray();
            if ($pet) {
                $newRp = max(0, (int) ($pet['rank_points'] ?? 1000) + $eloChange);
                $this->db->table('user_pets')->where('id', $petId)->update(['rank_points' => $newRp]);
            }
        }

        // 3. Invalidate leaderboard cache via event (no direct Sanctuary import)
        \App\Kernel\Events\DomainEvents::arenaRankingsChanged();

        return $newRp;
    }

    protected function calculateEloRp(int $playerElo, int $opponentElo, bool $isWin): int
    {
        $kFactor = 32;
        $expectedScore = 1 / (1 + pow(10, ($opponentElo - $playerElo) / 400));
        $actualScore = $isWin ? 1 : 0;
        $change = (int) round($kFactor * ($actualScore - $expectedScore));

        // Minimum gain of +15 RP per win so progress always feels rewarding
        if ($isWin && $change < 15) {
            $change = 15;
        }

        return $change;
    }

    protected function calculateBonusRewards(int $userId, int $baseGold, int $baseExp): array
    {
        $stats = $this->db->table('nethera')->where('id_nethera', $userId)->get()->getRowArray();
        $streak = (int) ($stats['current_win_streak'] ?? 0);

        $bonusGold = 0;
        $bonusExp = 0;

        if ($streak >= 3) {
            $bonusGold += (int) ($baseGold * 0.1);
            $bonusExp += (int) ($baseExp * 0.1);
        }

        if ($streak >= 5) {
            $bonusGold += (int) ($baseGold * 0.2);
            $bonusExp += (int) ($baseExp * 0.2);
        }

        return ['gold' => $bonusGold, 'exp' => $bonusExp];
    }
}
