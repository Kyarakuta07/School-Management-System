<?php

namespace App\Modules\Battle\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use App\Modules\Battle\Services\Arena1v1Service;

class Arena1v1Controller extends BaseApiController
{
    use IdempotencyTrait;

    protected Arena1v1Service $battleService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->battleService = service('arena1v1Service');
    }

    public function opponents()
    {
        $userId = $this->getUserId();
        if (!$userId)
            return $this->error('Unauthorized', 401);

        try {
            $opponents = $this->battleService->getOpponents($userId);
            return $this->success(['opponents' => $opponents], null, true);
        } catch (\Exception $e) {
            \log_message('error', "[Arena1v1Controller::opponents] " . $e->getMessage());
            return $this->error('Failed to fetch opponents');
        }
    }

    public function start()
    {
        $input = $this->getInput();
        $attackerPetId = (int) ($input['attacker_pet_id'] ?? 0);
        $defenderPetId = (int) ($input['defender_pet_id'] ?? 0);
        $userId = $this->userId;

        if (!$attackerPetId)
            return $this->error('Missing attacker pet ID');

        // Security: Ensure player owns the attacker
        $attacker = $this->battleService->getPetForBattle($attackerPetId, $userId);
        $defender = ($defenderPetId > 0) ? $this->battleService->getPetForBattle($defenderPetId) : null;

        if (!$attacker || ($defenderPetId > 0 && !$defender))
            return $this->error('Invalid pets for battle (Unauthorized or Fainted)');

        // For AI opponents, generate and cache synthetic pet data
        if ($defenderPetId <= 0 && !$defender) {
            $speciesId = (int) ($input['species_id'] ?? 0);
            $aiLevel = min((int) ($input['level'] ?? $attacker['level']), (int) $attacker['level'] + 5);
            $aiLevel = max(1, $aiLevel); // Floor at level 1
            $defender = $this->battleService->generateAISingleOpponent($speciesId, $aiLevel);
            // Cache AI data for subsequent attack/enemy-turn calls
            $session = \Config\Services::session();
            $session->set('ai_defender_1v1', $defender);
        } else {
            // Clear any cached AI data for real opponents
            $session = \Config\Services::session();
            $session->remove('ai_defender_1v1');
        }

        // Deduct quota only after successful validation
        $limiter = new \App\Kernel\Libraries\RateLimiter();
        $limiter->checkDailyLimit((string) $userId, 'battle', \App\Config\GameConfig::BATTLE_DAILY_QUOTA, 0, 'Asia/Jakarta');

        // Generate battle token to prevent result farming
        $battleToken = bin2hex(random_bytes(16));
        $session = $session ?? \Config\Services::session();
        $session->set('battle_1v1_token', $battleToken);
        $session->set('battle_1v1_attacker', $attackerPetId);
        $session->set('battle_1v1_defender', $defenderPetId);

        return $this->success([
            'success' => true,
            'attacker' => $attacker,
            'defender' => $defender,
            'userId' => $userId,
            'battle_token' => $battleToken
        ]);
    }

    public function attack1v1()
    {
        $input = $this->getInput();
        $attackerId = (int) ($input['attacker_pet_id'] ?? 0);
        $defenderId = (int) ($input['defender_pet_id'] ?? 0);
        $skillId = (int) ($input['skill_id'] ?? 0);

        try {
            $attackerRaw = $this->battleService->getPetForBattle($attackerId, $this->userId);
            $defenderRaw = ($defenderId > 0) ? $this->battleService->getPetForBattle($defenderId) : null;

            // For AI opponents, use cached data from session
            if (!$defenderRaw && $defenderId <= 0) {
                $defenderRaw = \Config\Services::session()->get('ai_defender_1v1');
            }

            if (!$attackerRaw || !$defenderRaw) {
                return $this->error('Invalid pets for battle (Unauthorized or Fainted)');
            }

            $attacker = $this->battleService->formatPetForArena($attackerRaw);
            $defender = $this->battleService->formatPetForArena($defenderRaw);

            $skill = $this->battleService->getSkill($skillId, $attacker);
            $result = $this->battleService->calculateDetailedDamage($attacker, $skill, $defender);
            $result['skill_name'] = $skill['skill_name'];
            $result['skill_element'] = $skill['skill_element'] ?? $attacker['element'];

            return $this->success($result);
        } catch (\Exception $e) {
            \log_message('error', "[Arena1v1Controller::attack1v1] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('Terjadi kesalahan server.');
        }
    }

    public function enemyTurn1v1()
    {
        $input = $this->getInput();
        $attackerId = (int) ($input['attacker_pet_id'] ?? 0);
        $defenderId = (int) ($input['defender_pet_id'] ?? 0);
        $hardMode = (bool) ($input['hard_mode'] ?? false);

        try {
            $attackerRaw = $this->battleService->getPetForBattle($attackerId, $this->userId);
            $defenderRaw = ($defenderId > 0) ? $this->battleService->getPetForBattle($defenderId) : null;

            // For AI opponents, use cached data from session
            if (!$defenderRaw && $defenderId <= 0) {
                $defenderRaw = \Config\Services::session()->get('ai_defender_1v1');
            }

            if (!$attackerRaw || !$defenderRaw) {
                return $this->error('Invalid pets for battle (Unauthorized or Fainted)');
            }

            // Note: for enemy turn, the AI is the attacker and player is the target
            $attacker = $this->battleService->formatPetForArena($attackerRaw); // Player pet
            $defender = $this->battleService->formatPetForArena($defenderRaw); // AI pet

            // AI selects from its own skills and attacks the player
            $skill = $this->battleService->getAiSkillSelection($defender, $attacker, $hardMode);
            $result = $this->battleService->calculateDetailedDamage($defender, $skill, $attacker);
            $result['skill_name'] = $skill['skill_name'];
            $result['skill_element'] = $skill['skill_element'] ?? $attacker['element'];

            return $this->success($result);
        } catch (\Exception $e) {
            \log_message('error', "[Arena1v1Controller::enemyTurn1v1] " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->error('Terjadi kesalahan server.');
        }
    }

    public function result()
    {
        if (!$this->acquireIdempotencyLock('arena_1v1_result', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $userId = $this->getUserId();
        $attackerId = (int) ($input['attacker_pet_id'] ?? 0);
        $defenderId = (int) ($input['defender_pet_id'] ?? 0);
        $winner = $input['winner'] ?? null;

        if (!$userId)
            return $this->error('Unauthorized', 401);

        // Validate battle token to prevent result farming
        $session = \Config\Services::session();
        $storedToken = $session->get('battle_1v1_token');
        $storedAttacker = (int) $session->get('battle_1v1_attacker');
        $storedDefender = (int) $session->get('battle_1v1_defender');

        if (!$storedToken || $storedAttacker !== $attackerId || $storedDefender !== $defenderId) {
            return $this->error('Invalid battle session. Please start a new battle.', 400, 'INVALID_SESSION');
        }

        // Consume the token — one-time use only
        $session->remove('battle_1v1_token');
        $session->remove('battle_1v1_attacker');
        $session->remove('battle_1v1_defender');

        try {
            $res = $this->battleService->resolveBattle($userId, $attackerId, $defenderId, $winner);
            return $this->success($res);
        } catch (\Exception $e) {
            log_message('error', '[Arena1v1] ' . $e->getMessage());
            return $this->error('Terjadi kesalahan server.');
        }
    }
}
