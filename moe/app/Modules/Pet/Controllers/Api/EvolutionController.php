<?php

namespace App\Modules\Pet\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Evolution API Controller
 * 
 * Ported from legacy EvolutionController.php
 * 
 * Endpoints:
 *   GET  /api/evolution/candidates â†’ candidates()
 *   POST /api/evolution/evolve     â†’ evolve()
 */
class EvolutionController extends BaseApiController
{
    use IdempotencyTrait;

    public function candidates(): ResponseInterface
    {
        $petId = (int) ($this->request->getGet('pet_id') ?? 0);

        if (!$petId) {
            return $this->error('Pet ID required', 400, 'VALIDATION_ERROR');
        }

        $evolutionService = service('evolutionService');
        $result = $evolutionService->getEvolutionCandidates($this->userId, $petId);

        if (!$result['success']) {
            return $this->error($result['error'], 400, 'EVOLUTION_ERROR');
        }

        return $this->success($result);
    }

    public function evolve(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('pet_evolution', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $petId = (int) ($input['pet_id'] ?? 0);
        $fodderIds = $input['fodder_ids'] ?? [];

        if (!$petId) {
            return $this->error('Pet ID required', 400, 'VALIDATION_ERROR');
        }

        if (!is_array($fodderIds) || count($fodderIds) !== 3) {
            return $this->error('Exactly 3 fodder pets required for evolution', 400, 'VALIDATION_ERROR');
        }

        $fodderIds = array_map('intval', $fodderIds);

        // Perform evolution
        $evolutionService = service('evolutionService');
        $result = $evolutionService->evolvePet($this->userId, $petId, $fodderIds);

        if (!$result['success']) {
            return $this->error($result['error'], 400, 'EVOLUTION_FAILED');
        }

        // Log transaction
        if ($result['gold_spent'] > 0) {
            $txModel = new \App\Modules\Trapeza\Models\TransactionModel();
            $txModel->logTransaction($this->userId, 0, $result['gold_spent'], 'evolution', 'Pet Evolution');
        }

        return $this->success($result, $result['message']);
    }
}
