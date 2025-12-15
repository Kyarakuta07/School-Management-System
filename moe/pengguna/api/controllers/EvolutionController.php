<?php
/**
 * MOE Pet System - Evolution Controller
 * Mediterranean of Egypt Virtual Pet Companion
 * 
 * Handles evolution-related endpoints:
 * - get_evolution_candidates: Get fodder pets for evolution
 * - evolve_manual: Perform manual evolution
 */

require_once __DIR__ . '/../BaseController.php';

class EvolutionController extends BaseController
{
    /**
     * GET: Get evolution candidates (fodder pets)
     */
    public function getEvolutionCandidates()
    {
        $this->requireGet();

        $pet_id = isset($_GET['pet_id']) ? (int) $_GET['pet_id'] : 0;

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        $result = getEvolutionCandidates($this->conn, $this->user_id, $pet_id);
        echo json_encode($result);
    }

    /**
     * POST: Perform manual evolution
     */
    public function evolveManual()
    {
        $this->requirePost();

        $input = $this->getInput();
        $pet_id = isset($input['pet_id']) ? (int) $input['pet_id'] : 0;
        $fodder_ids = isset($input['fodder_ids']) ? $input['fodder_ids'] : [];

        if (!$pet_id) {
            $this->error('Pet ID required');
            return;
        }

        if (!is_array($fodder_ids) || count($fodder_ids) !== 3) {
            $this->error('Exactly 3 fodder pets required for evolution');
            return;
        }

        // Convert to integers and validate
        $fodder_ids = array_map('intval', $fodder_ids);

        $result = evolvePetManual($this->conn, $this->user_id, $pet_id, $fodder_ids);

        if ($result['success']) {
            // Log gold spent on evolution
            $gold_spent = $result['gold_spent'] ?? 500;
            $this->logGoldTransaction($this->user_id, 0, $gold_spent, 'evolution', 'Pet evolution cost');
        }

        echo json_encode($result);
    }
}
