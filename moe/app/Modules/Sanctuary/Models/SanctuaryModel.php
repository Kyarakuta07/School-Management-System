<?php

namespace App\Modules\Sanctuary\Models;

use CodeIgniter\Model;

/**
 * Sanctuary Model
 * 
 * Single-table CRUD for the `sanctuary` table.
 * Complex JOIN queries moved to SanctuaryRepository.
 */
class SanctuaryModel extends Model
{
    protected $table = 'sanctuary';
    protected $primaryKey = 'id_sanctuary';
    protected $returnType = 'array';
    protected $allowedFields = ['nama_sanctuary', 'deskripsi'];

    /** Faction slug → DB name mapping (hardcoded for security) */
    public const FACTION_MAP = [
        'horus' => 'HORUS',
        'khonshu' => 'KHONSU',
        'osiris' => 'OSIRIS',
        'hathor' => 'HATHOR',
        'ammit' => 'AMMIT',
    ];

    /**
     * Get sanctuary by faction slug (whitelist-validated).
     * Returns null if slug is invalid.
     */
    public function getSanctuaryBySlug(string $slug): ?array
    {
        $slug = strtolower(trim($slug));

        if (!isset(self::FACTION_MAP[$slug])) {
            return null;
        }

        return $this->db->table('sanctuary')
            ->where('nama_sanctuary', self::FACTION_MAP[$slug])
            ->get()
            ->getRowArray();
    }

    /**
     * Get member count for a sanctuary.
     */
    public function getMemberCount(int $sanctuaryId): int
    {
        return (int) $this->db->table('nethera')
            ->where('id_sanctuary', $sanctuaryId)
            ->countAllResults();
    }

    /**
     * Check if a user belongs to a specific sanctuary.
     */
    public function isUserMember(int $userId, int $sanctuaryId): bool
    {
        return (bool) $this->db->table('nethera')
            ->where('id_nethera', $userId)
            ->where('id_sanctuary', $sanctuaryId)
            ->countAllResults();
    }
}
