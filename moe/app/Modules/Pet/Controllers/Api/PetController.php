<?php

namespace App\Modules\Pet\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;

use App\Modules\Pet\Interfaces\PetServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Pet API Controller
 * 
 * Ported from legacy moe/user/api/controllers/PetController.php
 * 
 * Endpoints:
 *   GET  /api/pets          → index()    (get all user pets)
 *   GET  /api/pets/active   → active()   (get active pet)
 *   POST /api/pets/activate → activate() (set active pet)
 *   POST /api/pets/rename   → rename()   (rename a pet)
 */
class PetController extends BaseApiController
{
    use IdempotencyTrait;

    protected PetServiceInterface $petService;
    protected \App\Modules\User\Services\ActivityLogService $activityLog;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->petService = service('petService');
        $this->activityLog = service('activityLog');
    }

    /**
     * GET /api/pets
     * Fetch all user's pets with stats
     * 
     * Legacy equivalent: router.php?action=get_pets
     */
    public function index(): ResponseInterface
    {
        $pets = $this->petService->getUserPetsWithStats($this->userId);

        return $this->success([
            'pets' => $pets,
            'count' => count($pets),
        ]);
    }

    /**
     * GET /api/pets/active
     * Get user's currently active pet
     * 
     * Legacy equivalent: router.php?action=get_active_pet
     */
    public function active(): ResponseInterface
    {
        $pet = $this->petService->getActivePet($this->userId);

        if (!$pet) {
            return $this->success(['pet' => null], 'No active pet found.');
        }

        return $this->success(['pet' => $pet]);
    }

    /**
     * POST /api/pets/activate
     * Set a pet as the active pet
     * 
     * Legacy equivalent: router.php?action=set_active
     */
    public function activate(): ResponseInterface
    {
        $input = $this->getInput();
        $petId = $input['pet_id'] ?? null;

        if (!$petId) {
            return $this->error('pet_id is required.', 400, 'VALIDATION_ERROR');
        }

        // Verify ownership
        if (!$this->petService->verifyOwnership($this->userId, (int) $petId)) {
            return $this->error('You do not own this pet.', 403, 'NOT_OWNED');
        }

        $result = $this->petService->setActivePet($this->userId, (int) $petId);

        if (!$result) {
            return $this->error('Failed to set active pet.', 500, 'DATABASE_ERROR');
        }

        return $this->success(['pet_id' => (int) $petId], 'Pet activated successfully!');
    }

    /**
     * POST /api/pets/rename
     * Rename a pet
     * 
     * Legacy equivalent: router.php?action=rename
     */
    public function rename(): ResponseInterface
    {
        $input = $this->getInput();
        $petId = $input['pet_id'] ?? null;
        $newName = $input['nickname'] ?? null;

        if (!$petId || !$newName) {
            return $this->error('pet_id and nickname are required.', 400, 'VALIDATION_ERROR');
        }

        // Sanitize nickname
        $newName = trim(strip_tags($newName));
        if (strlen($newName) < 1 || strlen($newName) > 20) {
            return $this->error('Nickname must be 1-20 characters.', 400, 'VALIDATION_ERROR');
        }

        // Verify ownership
        $pet = $this->petService->findPet((int) $petId);
        if (!$pet || $pet['user_id'] != $this->userId) {
            return $this->error('You do not own this pet.', 403, 'NOT_OWNED');
        }

        $oldName = $pet['nickname'] ?? 'Unnamed';
        $this->petService->renamePet((int) $petId, $newName);

        $this->activityLog->log('PET_RENAME', 'PET', "Renamed pet (ID: {$petId}) from '{$oldName}' to '{$newName}'", $this->userId);

        return $this->success([
            'pet_id' => (int) $petId,
            'nickname' => $newName,
        ], 'Pet renamed successfully!');
    }

    /**
     * POST /api/pets/shelter
     * Release a pet (send to shelter / delete)
     * 
     * Legacy equivalent: router.php?action=shelter
     */
    public function shelter(): ResponseInterface
    {
        $input = $this->getInput();
        $petId = $input['pet_id'] ?? null;
        $action = $input['action'] ?? 'shelter'; // 'shelter' or 'retrieve'

        if (!$petId) {
            return $this->error('pet_id is required.', 400, 'VALIDATION_ERROR');
        }

        // Verify ownership
        $pet = $this->petService->findPet((int) $petId);
        if (!$pet || $pet['user_id'] != $this->userId) {
            return $this->error('You do not own this pet.', 403, 'NOT_OWNED');
        }

        if ($action === 'retrieve') {
            $this->petService->retrievePet($this->userId, (int) $petId);

            $this->activityLog->log('PET_RETRIEVE', 'PET', "Retrieved pet (ID: {$petId}) from shelter.", $this->userId);

            return $this->success([
                'pet_id' => (int) $petId,
                'new_status' => 'ALIVE',
                'is_active' => 1
            ], 'Pet retrieved and activated!');
        }

        // Default: shelter action
        $this->petService->shelterPet($this->userId, (int) $petId);

        $this->activityLog->log('PET_SHELTER', 'PET', "Sent pet (ID: {$petId}) to shelter.", $this->userId);

        return $this->success([
            'pet_id' => (int) $petId,
            'new_status' => 'SHELTER'
        ], 'Pet sent to shelter. It will no longer lose stats.');
    }

    /**
     * POST /api/pets/sell
     * Sell a pet for gold
     * 
     * Formula: base_rarity * floor(sqrt(level))
     * Base: Common=1, Uncommon=2, Rare=3, Epic=10, Legendary=25
     * 
     * Legacy equivalent: router.php?action=sell_pet
     */
    public function sell(): ResponseInterface
    {
        if (!$this->acquireIdempotencyLock('pet_selling', $this->userId)) {
            return $this->error('Request already in progress. Please wait.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $petId = $input['pet_id'] ?? null;

        if (!$petId) {
            return $this->error('pet_id is required.', 400, 'VALIDATION_ERROR');
        }

        // Verify ownership and get full details including rarity
        $pet = $this->petService->getPetWithSpecies((int) $petId);

        if (!$pet || $pet['user_id'] != $this->userId) {
            return $this->error('You do not own this pet.', 403, 'NOT_OWNED');
        }

        if ($pet['is_active']) {
            return $this->error('Cannot sell active pet.', 400, 'ACTIVE_PET');
        }

        // Calculate sell price
        $rarity = $pet['rarity'] ?? 'Common';
        $level = (int) $pet['level'];

        $basePrices = [
            'Common' => 1,
            'Uncommon' => 2,
            'Rare' => 3,
            'Epic' => 10,
            'Legendary' => 25
        ];

        $base = $basePrices[$rarity] ?? 1;
        $levelMultiplier = max(1, floor(sqrt($level)));
        $sellPrice = $base * $levelMultiplier;

        // Start transaction — single TX with addGoldRaw (no nested TX)
        $this->db->transBegin();

        try {
            // 1. Add gold via raw method (no nested TX)
            $goldService = service('goldService');
            $goldService->addGoldRaw($this->userId, $sellPrice, 'sell_pet', "Sold pet: " . ($pet['nickname'] ?: $pet['species_name']));

            // 2. Delete pet
            $this->petService->deletePet((int) $petId);

            // 3. Log the action
            $this->activityLog->log('PET_SELL', 'PET', "Sold pet (ID: {$petId}, Rarity: {$rarity}, Level: {$level}) for {$sellPrice} gold.", $this->userId);

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->error('Transaction failed.', 500, 'DB_ERROR');
        }

        return $this->success([
            'pet_id' => (int) $petId,
            'gold_earned' => $sellPrice,
            'remaining_gold' => $this->getUserGold()
        ], "Sold pet for {$sellPrice} Gold!");
    }
}
