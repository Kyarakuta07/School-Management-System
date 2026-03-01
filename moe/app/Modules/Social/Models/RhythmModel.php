<?php

namespace App\Modules\Social\Models;

use CodeIgniter\Model;

/**
 * RhythmModel — Handles all rhythm_songs, rhythm_beatmaps, and rhythm_scores DB access.
 *
 * Replaces scattered db->table() calls across RhythmService, RhythmController,
 * and ImportController with a single, centralized data-access layer.
 */
class RhythmModel extends Model
{
    protected $table = 'rhythm_songs';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'title', 'artist', 'audio_file', 'bpm',
        'duration_sec', 'difficulty', 'is_active', 'created_at',
    ];

    protected $useTimestamps = false;

    // ── Songs ─────────────────────────────────────────

    /**
     * Get all active songs, ordered by difficulty.
     */
    public function getActiveSongs(): array
    {
        return $this->where('is_active', 1)
            ->orderBy('difficulty', 'ASC')
            ->findAll();
    }

    /**
     * Get a single song by ID.
     */
    public function getSong(int $songId): ?array
    {
        return $this->find($songId);
    }

    /**
     * Insert a new song and return its ID.
     */
    public function insertSong(array $data): int
    {
        $this->insert($data);
        return $this->getInsertID();
    }

    // ── Beatmaps ──────────────────────────────────────

    /**
     * Get beatmap for a song.
     */
    public function getBeatmap(int $songId): ?array
    {
        return $this->db->table('rhythm_beatmaps')
            ->where('song_id', $songId)
            ->get()
            ->getRowArray();
    }

    /**
     * Get beatmap with parsed notes.
     */
    public function getBeatmapWithNotes(int $songId): ?array
    {
        $beatmap = $this->getBeatmap($songId);

        if ($beatmap) {
            $dataKey = isset($beatmap['beatmap_data']) ? 'beatmap_data' : 'notes_data';
            if (!empty($beatmap[$dataKey])) {
                $beatmap['notes'] = json_decode($beatmap[$dataKey], true);
            }
        }

        return $beatmap;
    }

    /**
     * Insert a new beatmap record.
     */
    public function insertBeatmap(array $data): void
    {
        $this->db->table('rhythm_beatmaps')->insert($data);
    }

    // ── Scores ────────────────────────────────────────

    /**
     * Insert a score record.
     */
    public function insertScore(array $data): int
    {
        $this->db->table('rhythm_scores')->insert($data);
        return $this->db->insertID();
    }

    /**
     * Get user's best score for a song.
     */
    public function getHighscore(int $userId, int $songId): ?array
    {
        return $this->db->table('rhythm_scores')
            ->where('user_id', $userId)
            ->where('song_id', $songId)
            ->orderBy('score', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();
    }

    // ── Import (transactional) ────────────────────────

    /**
     * Import a song + beatmap atomically. Returns song ID or 0 on failure.
     */
    public function importSongWithBeatmap(array $songData, array $beatmapData): int
    {
        $this->db->transBegin();

        try {
            $songId = $this->insertSong($songData);

            $beatmapData['song_id'] = $songId;
            $this->insertBeatmap($beatmapData);

            $this->db->transCommit();
            return $songId;
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', '[RhythmModel] importSongWithBeatmap failed: ' . $e->getMessage());
            return 0;
        }
    }
}
