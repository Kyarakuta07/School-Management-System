<?php

namespace App\Modules\Battle\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use App\Modules\Battle\Services\Arena3v3Service;

class Arena3v3Controller extends BaseApiController
{
    use IdempotencyTrait;

    protected Arena3v3Service $battleService;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->battleService = service('arena3v3Service');
    }

    public function opponents3v3()
    {
        $userId = $this->getUserId();
        $db = \Config\Database::connect();

        $opponents = $db->table('nethera')->where('id_nethera !=', $userId)->orderBy('RAND()')->limit(5)->get()->getResultArray();
        $results = [];
        foreach ($opponents as $opp) {
            $pets = $db->table('user_pets AS up')
                ->select('up.*, ps.name AS species_name, ps.element, ps.img_egg, ps.img_baby, ps.img_adult')
                ->join('pet_species AS ps', 'ps.id = up.species_id')
                ->where('up.user_id', $opp['id_nethera'])
                ->where('up.status', 'ALIVE')
                ->orderBy('up.level', 'DESC')
                ->limit(3)
                ->get()->getResultArray();
            if (count($pets) >= 1) {
                $results[] = ['id' => $opp['id_nethera'], 'username' => $opp['username'], 'pets' => $pets];
            }
        }
        return $this->success($results);
    }

    public function start3v3()
    {
        $input = $this->getInput();
        $userId = $this->getUserId();
        $petIds = $input['pet_ids'] ?? [];
        $oppId = (int) ($input['opponent_id'] ?? 0);

        if (empty($petIds))
            return $this->error('No pets selected');

        // A9 fix: Validate opponent_id if provided
        if ($oppId > 0) {
            if ($oppId === $userId) {
                return $this->error('Cannot battle yourself', 400, 'VALIDATION_ERROR');
            }
            $oppExists = \Config\Database::connect()->table('nethera')
                ->where('id_nethera', $oppId)->countAllResults();
            if ($oppExists === 0) {
                return $this->error('Opponent not found', 404, 'NOT_FOUND');
            }
        }

        try {
            $state = $this->battleService->initBattle($userId, $petIds, $oppId ?: null);

            // Deduct quota only after successful battle creation
            $limiter = new \App\Kernel\Libraries\RateLimiter();
            $limiter->checkDailyLimit((string) $userId, 'battle', \App\Config\GameConfig::BATTLE_DAILY_QUOTA, 0, 'Asia/Jakarta');

            return $this->success(['battle_id' => $state['battle_id'], 'battle_state' => $state]);
        } catch (\Exception $e) {
            log_message('error', '[Arena3v3] ' . $e->getMessage());
            return $this->error('Terjadi kesalahan server.');
        }
    }

    public function battleState()
    {
        $battleId = $this->request->getGet('battle_id');
        /** @var array|null $state */
        $state = \Config\Services::cache()->get($battleId);

        // A3 fix: Verify battle belongs to requesting user
        if (!$state || !isset($state['user_id']) || (int) $state['user_id'] !== $this->userId) {
            return $this->error('Battle not found', 404, 'NOT_FOUND');
        }

        return $this->success(['battle_state' => $state]);
    }

    public function attack()
    {
        $input = $this->getInput();
        $battleId = $input['battle_id'] ?? '';
        $skillId = (int) ($input['skill_id'] ?? 0);
        $targetIndex = (int) ($input['target_index'] ?? 0);
        $userId = $this->getUserId();

        try {
            $res = $this->battleService->applyAttack($battleId, $userId, $skillId, $targetIndex);
            return $this->success($res);
        } catch (\Exception $e) {
            log_message('error', '[Arena3v3] ' . $e->getMessage());
            return $this->error('Terjadi kesalahan server.');
        }
    }

    public function enemyTurn()
    {
        $input = $this->getInput();
        $battleId = $input['battle_id'] ?? '';
        try {
            // A3 fix: Pass userId to verify ownership in service
            $res = $this->battleService->processEnemyTurn($battleId, $this->userId);
            return $this->success($res);
        } catch (\Exception $e) {
            log_message('error', '[Arena3v3] ' . $e->getMessage());
            return $this->error('Terjadi kesalahan server.');
        }
    }

    public function finish3v3()
    {
        if (!$this->acquireIdempotencyLock('arena_3v3_finish', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $battleId = $input['battle_id'] ?? '';
        $userId = $this->getUserId();
        try {
            $res = $this->battleService->finishBattle($battleId, $userId);
            return $this->success($res);
        } catch (\Exception $e) {
            log_message('error', '[Arena3v3] ' . $e->getMessage());
            return $this->error('Terjadi kesalahan server.');
        }
    }

    public function switchPet()
    {
        $input = $this->getInput();
        $battleId = $input['battle_id'] ?? '';
        $newIndex = (int) ($input['new_index'] ?? 0);
        $userId = $this->getUserId();

        try {
            $res = $this->battleService->switchPet($battleId, $userId, $newIndex);
            return $this->success($res);
        } catch (\Exception $e) {
            log_message('error', '[Arena3v3] ' . $e->getMessage());
            return $this->error('Terjadi kesalahan server.');
        }
    }
}
