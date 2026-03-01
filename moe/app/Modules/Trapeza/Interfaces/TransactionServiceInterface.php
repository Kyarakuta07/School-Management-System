<?php

namespace App\Modules\Trapeza\Interfaces;

/**
 * TransactionServiceInterface — Contract for transaction logging.
 *
 * Cross-domain consumers (GoldService, RewardController, EvolutionController)
 * should depend on this interface instead of importing TransactionModel directly.
 */
interface TransactionServiceInterface
{
    /** Log a gold transaction (sender/receiver can be null for system operations). */
    public function logTransaction(?int $senderId, ?int $receiverId, int $amount, string $type, string $description): bool;
}
