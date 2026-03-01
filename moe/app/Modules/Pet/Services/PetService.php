<?php

namespace App\Modules\Pet\Services;

use App\Modules\Pet\Interfaces\PetServiceInterface;
use App\Modules\Pet\Models\PetModel;
use CodeIgniter\Database\BaseConnection;

/**
 * PetService
 *
 * Single point of contact for all pet-domain operations.
 * Delegates to PetModel internally. All controllers and services
 * should use this service instead of accessing PetModel directly.
 */
class PetService implements PetServiceInterface
{
    protected BaseConnection $db;
    protected PetModel $petModel;

    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
        $this->petModel = new PetModel();
    }

    // ── Read Operations ───────────────────────────────────

    public function getActivePet(int $userId): ?array
    {
        return $this->petModel->getActivePet($userId);
    }

    public function getUserPetsWithStats(int $userId): array
    {
        return $this->petModel->getUserPetsWithStats($userId);
    }

    public function getPetWithSpecies(int $petId): ?array
    {
        return $this->petModel->getPetWithSpecies($petId);
    }

    public function getPetWithDetails(int $petId): ?array
    {
        return $this->petModel->getPetWithDetails($petId);
    }

    public function getPetSkills(int $speciesId): array
    {
        return $this->petModel->getPetSkills($speciesId);
    }

    public function getUserPetStats(int $userId): array
    {
        return $this->petModel->getUserPetStats($userId);
    }

    public function findPet(int $petId): ?array
    {
        $result = $this->petModel->find($petId);
        return is_array($result) ? $result : null;
    }

    public function verifyOwnership(int $userId, int $petId): bool
    {
        return $this->petModel->verifyOwnership($userId, $petId);
    }

    // ── Presentation Helpers ──────────────────────────────

    /**
     * Resolve the correct pet image filename based on evolution stage.
     *
     * Accepts a pet array with 'evolution_stage' and 'img_egg', 'img_baby', 'img_adult'.
     * Falls back through stages if the preferred image is empty.
     */
    public function getPetImageUrl(array $pet): string
    {
        $stage = $pet['evolution_stage'] ?? 'egg';
        $imgEgg = $pet['img_egg'] ?? '';
        $imgBaby = $pet['img_baby'] ?? '';
        $imgAdult = $pet['img_adult'] ?? '';

        return match ($stage) {
            'adult' => $imgAdult ?: $imgBaby ?: $imgEgg ?: 'placeholder.png',
            'baby' => $imgBaby ?: $imgEgg ?: $imgAdult ?: 'placeholder.png',
            default => $imgEgg ?: $imgBaby ?: $imgAdult ?: 'placeholder.png',
        };
    }

    // ── Battle Operations ─────────────────────────────────

    public function calculateBattleStats(array $attacker, array $defender): array
    {
        return $this->petModel->calculateBattleStats($attacker, $defender);
    }

    public function getOpponents(int $userId, int $limit = 5): array
    {
        return $this->petModel->getOpponents($userId, $limit);
    }

    // ── Write Operations ──────────────────────────────────

    public function setActivePet(int $userId, int $petId): bool
    {
        return $this->petModel->setActivePet($userId, $petId);
    }

    public function renamePet(int $petId, string $nickname): void
    {
        $this->petModel->update($petId, ['nickname' => $nickname]);
    }

    public function shelterPet(int $userId, int $petId): void
    {
        $this->db->transStart();

        // If this was the active pet, deactivate all
        $pet = $this->petModel->find($petId);
        if ($pet && !empty($pet['is_active'])) {
            $this->petModel->where('user_id', $userId)->set(['is_active' => 0])->update();
        }

        $this->petModel->update($petId, [
            'status' => 'SHELTER',
            'is_active' => 0,
        ]);

        $this->db->transComplete();
    }

    public function retrievePet(int $userId, int $petId): void
    {
        $this->db->transStart();

        $this->petModel->update($petId, ['status' => 'ALIVE']);
        $this->petModel->setActivePet($userId, $petId);

        $this->db->transComplete();
    }

    public function deletePet(int $petId): void
    {
        $this->petModel->delete($petId);
    }

    /**
     * Update pet mood only — uses dedicated mutator (bypasses $allowedFields safely).
     */
    public function updateMood(int $petId, int $mood): void
    {
        $this->petModel->updateMood($petId, $mood);
    }

    /**
     * Update writable pet fields only (nickname, hunger, mood, status, is_active).
     * Does NOT allow computed fields (level, exp, hp, total_wins, etc.).
     */
    public function updatePet(int $petId, array $data): void
    {
        // Whitelist: only allow fields that are in PetModel.$allowedFields
        $writable = ['nickname', 'hunger', 'mood', 'status', 'is_active', 'last_update_timestamp'];
        $filtered = array_intersect_key($data, array_flip($writable));
        if (!empty($filtered)) {
            $this->petModel->update($petId, $filtered);
        }
    }

    public function addExp(int $petId, int $amount): array
    {
        return $this->petModel->addExp($petId, $amount);
    }

    public function addExpRaw(int $petId, int $amount): array
    {
        return $this->petModel->addExpRaw($petId, $amount);
    }
}
