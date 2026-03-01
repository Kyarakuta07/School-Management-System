<?php

namespace App\Modules\Pet\Interfaces;

/**
 * PetServiceInterface
 *
 * Abstraction layer for all pet-domain operations.
 * Controllers and Services should depend on this interface
 * instead of coupling directly to PetModel.
 */
interface PetServiceInterface
{
    // ── Read Operations ───────────────────────────────────

    /** Get user's currently active pet with species info. */
    public function getActivePet(int $userId): ?array;

    /** Get all pets belonging to a user, with species info and batched stat decay. */
    public function getUserPetsWithStats(int $userId): array;

    /** Get a single pet with its species data. */
    public function getPetWithSpecies(int $petId): ?array;

    /** Get a single pet with species and owner name. */
    public function getPetWithDetails(int $petId): ?array;

    /** Get skills associated with a species. */
    public function getPetSkills(int $speciesId): array;

    /** Get aggregate statistics for a user's pet collection. */
    public function getUserPetStats(int $userId): array;

    /** Find a pet by its primary key. */
    public function findPet(int $petId): ?array;

    /** Verify if a pet belongs to a user. */
    public function verifyOwnership(int $userId, int $petId): bool;

    // ── Battle Operations ─────────────────────────────────

    /** Resolve pet image filename based on evolution stage. */
    public function getPetImageUrl(array $pet): string;

    /** Calculate battle-ready stats for a pair of pets. */
    public function calculateBattleStats(array $attacker, array $defender): array;

    /** Get random opponents for arena matchmaking. */
    public function getOpponents(int $userId, int $limit = 5): array;

    // ── Write Operations ──────────────────────────────────

    /** Set a pet as active (deactivate others first). */
    public function setActivePet(int $userId, int $petId): bool;

    /** Rename a pet. */
    public function renamePet(int $petId, string $nickname): void;

    /** Send a pet to shelter (soft-delete). */
    public function shelterPet(int $userId, int $petId): void;

    /** Retrieve a pet from shelter and auto-activate. */
    public function retrievePet(int $userId, int $petId): void;

    /** Delete a pet permanently (sell). */
    public function deletePet(int $petId): void;

    /** Update writable pet fields only (nickname, hunger, mood, status, is_active). */
    public function updatePet(int $petId, array $data): void;

    /** Update pet mood — dedicated safe mutator, clamps to 0-100. */
    public function updateMood(int $petId, int $mood): void;

    /** Add EXP to a pet, handling level-ups (standalone with own TX). */
    public function addExp(int $petId, int $amount): array;

    /** Add EXP to a pet — raw version WITHOUT own TX. Caller must wrap in transaction. */
    public function addExpRaw(int $petId, int $amount): array;
}
