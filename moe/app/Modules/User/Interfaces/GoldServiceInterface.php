<?php

namespace App\Modules\User\Interfaces;

use CodeIgniter\Database\BaseConnection;

/**
 * GoldServiceInterface — Contract for gold operations.
 */
interface GoldServiceInterface
{
    public function getBalance(int $userId): int;
    public function addGoldRaw(int $userId, int $amount, string $type, string $description): bool;
    public function subtractGoldRaw(int $userId, int $amount, string $type, string $description): bool;
    public function addGold(int $userId, int $amount, string $type, string $description): bool;
    public function subtractGold(int $userId, int $amount, string $type, string $description): bool;
    public function transferGold(int $senderId, int $receiverId, int $amount, string $description = 'User transfer'): bool;
}
