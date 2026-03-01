<?php

namespace App\Modules\Battle\Controllers;

use App\Kernel\BaseController;

/**
 * BattlePageController — Serves battle arena pages (1v1 and 3v3).
 * Ported from legacy moe/user/battle_arena.php and battle_3v3.php.
 */
class BattlePageController extends BaseController
{
    protected $petService;

    public function __construct()
    {
        $this->petService = service('petService');
    }

    /**
     * 1v1 Battle Arena — turn-based combat page.
     * GET /battle?attacker_id=X&defender_id=Y
     * Supports AI/Wild opponents when defender_id=0 (reads from session cache).
     */
    public function arena()
    {
        $session = \Config\Services::session();
        $userId = $session->get('id_nethera');

        $attackerPetId = (int) ($this->request->getGet('attacker_id') ?: $this->request->getGet('attacker'));
        $defenderPetId = (int) ($this->request->getGet('defender_id') ?: $this->request->getGet('defender'));

        if (!$attackerPetId) {
            return redirect()->to(base_url('pet?tab=arena&error=missing_pets'));
        }

        // Get attacker pet data (user's pet)
        $attacker = $this->petService->getPetWithSpecies($attackerPetId);

        if (!$attacker || $attacker['user_id'] != $userId) {
            return redirect()->to(base_url('pet?tab=arena&error=invalid_attacker'));
        }

        // ── Resolve defender: AI (session) or PvP (database) ──
        if ($defenderPetId > 0) {
            // PvP: fetch real pet from database (existing flow, untouched)
            $defender = $this->petService->getPetWithDetails($defenderPetId);
        } else {
            // AI/Wild: read cached data set by Arena1v1Controller::start()
            $defender = $session->get('ai_defender_1v1');
        }

        if (!$defender) {
            return redirect()->to(base_url('pet?tab=arena&error=invalid_defender'));
        }

        // Get attacker skills
        $attackerSkills = $this->petService->getPetSkills($attacker['species_id']);

        // Get defender skills
        $defenderSkills = $this->petService->getPetSkills($defender['species_id'] ?? 0);

        // Calculate battle stats via Service
        $stats = $this->petService->calculateBattleStats($attacker, $defender);

        return view('App\Modules\Battle\Views\battle_arena', array_merge([
            'attacker' => $attacker,
            'defender' => $defender,
            'attackerSkills' => $attackerSkills,
            'defenderSkills' => $defenderSkills,
            'attackerPetId' => $attackerPetId,
            'defenderPetId' => $defenderPetId,
            'attackerImg' => service('petService')->getPetImageUrl($attacker),
            'defenderImg' => service('petService')->getPetImageUrl($defender),
        ], $stats));
    }

    /**
     * Sanctuary War Battle — turn-based combat page.
     * GET /battle-war?attacker_id=X&defender_id=Y&war_id=Z
     */
    public function arenaWar()
    {
        $userId = \Config\Services::session()->get('id_nethera');

        $attackerPetId = (int) $this->request->getGet('attacker_id');
        $defenderPetId = (int) $this->request->getGet('defender_id');
        $warId = (int) $this->request->getGet('war_id');

        if (!$attackerPetId || !$defenderPetId || !$warId) {
            return redirect()->to(base_url('pet?tab=war&error=missing_battle_params'));
        }

        $attacker = $this->petService->getPetWithSpecies($attackerPetId);
        if (!$attacker || $attacker['user_id'] != $userId) {
            return redirect()->to(base_url('pet?tab=war&error=invalid_attacker'));
        }

        $defender = $this->petService->getPetWithDetails($defenderPetId);
        if (!$defender) {
            return redirect()->to(base_url('pet?tab=war&error=invalid_defender'));
        }

        $attackerSkills = $this->petService->getPetSkills($attacker['species_id']);
        $defenderSkills = $this->petService->getPetSkills($defender['species_id']);
        $stats = $this->petService->calculateBattleStats($attacker, $defender);

        return view('App\Modules\Battle\Views\war_arena', array_merge([
            'attacker' => $attacker,
            'defender' => $defender,
            'attackerSkills' => $attackerSkills,
            'defenderSkills' => $defenderSkills,
            'attackerPetId' => $attackerPetId,
            'defenderPetId' => $defenderPetId,
            'warId' => $warId,
            'attackerImg' => service('petService')->getPetImageUrl($attacker),
            'defenderImg' => service('petService')->getPetImageUrl($defender),
        ], $stats));
    }

    /**
     * 3v3 Battle Arena — landscape-only combat page.
     * GET /battle-3v3?battle_id=X
     */
    public function arena3v3()
    {
        $battleId = $this->request->getGet('battle_id') ?? '';

        if (empty($battleId)) {
            return redirect()->to(base_url('pet?tab=arena3v3'));
        }

        return view('App\Modules\Battle\Views\battle_3v3', [
            'battleId' => $battleId,
        ]);
    }


}
