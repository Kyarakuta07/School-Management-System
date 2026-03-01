<?php

namespace App\Modules\Social\Services;

use App\Modules\Social\Models\RhythmModel;

/**
 * RhythmService — Business logic for rhythm game operations.
 * Delegates all DB access to RhythmModel.
 */
class RhythmService
{
    protected RhythmModel $rhythmModel;

    public function __construct()
    {
        $this->rhythmModel = new RhythmModel();
    }

    /**
     * Get all active songs.
     */
    public function getSongs(): array
    {
        return $this->rhythmModel->getActiveSongs();
    }

    /**
     * Get a single song by ID.
     */
    public function getSong(int $songId): ?array
    {
        return $this->rhythmModel->getSong($songId);
    }

    /**
     * Get beatmap for a song (with parsed notes).
     */
    public function getBeatmap(int $songId): ?array
    {
        return $this->rhythmModel->getBeatmapWithNotes($songId);
    }

    /**
     * Get raw beatmap data (for note counting).
     */
    public function getRawBeatmap(int $songId): ?array
    {
        return $this->rhythmModel->getBeatmap($songId);
    }

    /**
     * Submit a score via model.
     */
    public function submitScore(array $data): int
    {
        return $this->rhythmModel->insertScore($data);
    }

    /**
     * Get highscore for a song (user's best).
     */
    public function getHighscore(int $userId, int $songId): ?array
    {
        return $this->rhythmModel->getHighscore($userId, $songId);
    }

    /**
     * Import beatmap from osu! .osz file.
     */
    public function importBeatmap(array $songData, array $notesData): int
    {
        return $this->rhythmModel->importSongWithBeatmap($songData, [
            'difficulty' => $songData['difficulty'] ?? 'Normal',
            'notes_data' => json_encode($notesData),
            'total_notes' => count($notesData),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    // ================================================
    // BEATMAP GENERATION (moved from controller)
    // ================================================

    /**
     * Generate a deterministic beatmap for a song that has no custom beatmap.
     * Uses seeded random so the same song always produces the same map.
     */
    public function generateBeatmap(array $song): array
    {
        $bpm = (int) ($song['bpm'] ?? 120);
        $duration = (int) ($song['duration_sec'] ?? 60);
        $songId = (int) ($song['id'] ?? 0);

        $diffMap = ['Easy' => 1, 'Medium' => 2, 'Hard' => 3, 'Expert' => 4];
        $difficulty = $diffMap[$song['difficulty'] ?? 'Medium'] ?? 2;

        // Seed random with song ID for deterministic output
        mt_srand($songId * 31337);

        $notes = [];
        $beatInterval = 60000 / $bpm; // ms per beat
        $lanes = 4;
        $totalBeats = (int) ($duration * 1000 / $beatInterval);

        for ($i = 0; $i < $totalBeats; $i++) {
            $time = (int) ($i * $beatInterval);

            // Skip some beats based on difficulty
            if ($difficulty <= 2 && $i % 2 !== 0)
                continue;
            if ($difficulty <= 1 && $i % 4 !== 0)
                continue;

            $type = 'tap';
            $noteDuration = 0;

            // Hold notes only when type roll says so
            if (mt_rand(1, 100) <= 15 * $difficulty) {
                $type = 'hold';
                $noteDuration = (int) ($beatInterval * mt_rand(1, 3));
            }

            $notes[] = [
                'time' => $time,
                'lane' => mt_rand(0, $lanes - 1),
                'type' => $type,
                'duration' => $type === 'hold' ? $noteDuration : 0,
            ];
        }

        // Reset random seed
        mt_srand();

        return $notes;
    }

    /**
     * Calculate letter grade based on accuracy.
     */
    public function calculateRank(int $perfectPlus, int $perfect, int $great, int $good, int $miss): string
    {
        $total = $perfectPlus + $perfect + $great + $good + $miss;
        if ($total === 0)
            return 'F';

        // Weights: PERFECT+ and PERFECT = 100%, GREAT = 80%, GOOD = 50%, MISS = 0%
        $accuracy = (($perfectPlus * 100 + $perfect * 100 + $great * 80 + $good * 50) / ($total * 100)) * 100;

        if ($accuracy >= 95 && $miss === 0)
            return 'S';
        if ($accuracy >= 90)
            return 'A';
        if ($accuracy >= 80)
            return 'B';
        if ($accuracy >= 70)
            return 'C';
        if ($accuracy >= 60)
            return 'D';
        return 'F';
    }
}
