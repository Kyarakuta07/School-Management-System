<?php

namespace App\Modules\Social\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Kernel\Traits\IdempotencyTrait;
use App\Config\GameConfig;

use CodeIgniter\HTTP\ResponseInterface;

/**
 * Rhythm Game API Controller
 * 
 * Delegates ALL business logic to RhythmService.
 * Controller only handles HTTP ↔ Service translation.
 * 
 * Endpoints:
 *   GET  /api/rhythm/songs       → songs()
 *   GET  /api/rhythm/beatmap     → beatmap()
 *   POST /api/rhythm/score       → submitScore()
 *   GET  /api/rhythm/highscore   → highscore()
 */
class RhythmController extends BaseApiController
{
    use IdempotencyTrait;
    protected \App\Modules\Social\Services\RhythmService $rhythmService;

    public function __construct()
    {
        $this->rhythmService = new \App\Modules\Social\Services\RhythmService();
    }

    public function songs(): ResponseInterface
    {
        $songs = $this->rhythmService->getSongs();
        return $this->success(['songs' => $songs]);
    }

    public function beatmap(): ResponseInterface
    {
        $songId = (int) ($this->request->getGet('song_id') ?? 0);
        if (!$songId)
            return $this->error('Song ID required', 400, 'VALIDATION_ERROR');

        $song = $this->rhythmService->getSong($songId);
        if (!$song)
            return $this->error('Song not found', 404, 'NOT_FOUND');

        // getBeatmap returns parsed notes via model (handles both beatmap_data and notes_data columns)
        $beatmap = $this->rhythmService->getBeatmap($songId);
        $notes = null;

        if ($beatmap) {
            // Model's getBeatmapWithNotes stores parsed JSON in 'notes' key
            $notes = $beatmap['notes'] ?? null;

            // Fallback: try raw column names
            if (!$notes) {
                $dataKey = isset($beatmap['beatmap_data']) ? 'beatmap_data' : 'notes_data';
                if (!empty($beatmap[$dataKey])) {
                    $notes = json_decode($beatmap[$dataKey], true);
                }
            }
        }

        // If still no beatmap, generate one
        if (!$notes || !is_array($notes)) {
            $notes = $this->rhythmService->generateBeatmap($song);
        }

        return $this->success([
            'song' => $song,
            'beatmap' => $notes,
            'bpm' => (int) ($song['bpm'] ?? 120),
        ]);
    }

    public function submitScore(): ResponseInterface
    {
        // Anti-spam: 30 second cooldown between score submissions (prevents rapid fail farming)
        if (!$this->acquireIdempotencyLock('rhythm_score_submission', $this->userId, GameConfig::RHYTHM_SCORE_SUBMIT_CD)) {
            return $this->error('Please wait before submitting another score.', 429, 'DUPLICATE_REQUEST');
        }

        $input = $this->getInput();
        $songId = (int) ($input['song_id'] ?? 0);
        $score = max(0, (int) ($input['score'] ?? 0)); // No negative scores
        $maxCombo = max(0, (int) ($input['max_combo'] ?? 0));
        $perfectPlusHits = max(0, (int) ($input['perfect_plus_hits'] ?? 0));
        $perfectHits = max(0, (int) ($input['perfect_hits'] ?? 0));
        $greatHits = max(0, (int) ($input['great_hits'] ?? 0));
        $goodHits = max(0, (int) ($input['good_hits'] ?? 0));
        $missHits = max(0, (int) ($input['miss_hits'] ?? 0));
        $gameDuration = max(0, (int) ($input['game_duration'] ?? 0)); // ms played

        if (!$songId)
            return $this->error('Song ID required', 400, 'VALIDATION_ERROR');

        // Verify song exists
        $song = $this->rhythmService->getSong($songId);
        if (!$song) {
            return $this->error('Song not found', 404, 'NOT_FOUND');
        }

        // Anti-exploit: minimum game duration (10 seconds = 10000ms)
        // Prevents instant-fail farming
        if ($gameDuration < GameConfig::RHYTHM_MIN_DURATION_MS) {
            return $this->success([
                'score' => 0,
                'rank' => 'F',
                'gold_earned' => 0,
                'exp_earned' => 0,
            ], 'Game too short. No rewards earned.');
        }

        // Count total notes in beatmap for validation ceiling
        $beatmap = $this->rhythmService->getRawBeatmap($songId);
        $totalNotes = 0;
        if ($beatmap) {
            $dataKey = isset($beatmap['beatmap_data']) ? 'beatmap_data' : 'notes_data';
            $notes = !empty($beatmap[$dataKey]) ? json_decode($beatmap[$dataKey], true) : null;
            $totalNotes = is_array($notes) ? count($notes) : 0;
        }
        if ($totalNotes === 0) {
            $bpm = (int) ($song['bpm'] ?? 120);
            $duration = (int) ($song['duration_sec'] ?? 60);
            $totalNotes = max(1, (int) ($duration * $bpm / 60));
        }

        // Validate hit counts: total must not exceed note count + reasonable margin
        $totalHits = $perfectPlusHits + $perfectHits + $greatHits + $goodHits + $missHits;
        if ($totalHits > $totalNotes + 10) { // small margin for edge cases
            return $this->error('Invalid hit data', 400, 'VALIDATION_ERROR');
        }

        // Cap score and combo
        $maxPossibleScore = $totalNotes * 1000;
        $score = min($score, $maxPossibleScore, 2000000);
        $maxCombo = min($maxCombo, $totalNotes);

        // Server-side rank calculation (never trust client)
        $rank = $this->rhythmService->calculateRank($perfectPlusHits, $perfectHits, $greatHits, $goodHits, $missHits);
        $isFail = (bool) ($input['is_fail'] ?? false);

        // ──── Anti-farm reward system ────
        // Daily cap: max 10 rewarded plays per day
        $todayPlays = $this->db->table('rhythm_scores')
            ->where('user_id', $this->userId)
            ->where('created_at >=', date('Y-m-d 00:00:00'))
            ->countAllResults();

        $rewardMultiplier = ($todayPlays < GameConfig::RHYTHM_DAILY_PLAY_CAP) ? 1.0 : 0.0; // No rewards after cap

        // Reward scaling: no rewards for fails or very low scores
        $goldReward = 0;
        $expReward = 0;

        if (!$isFail && $rank !== 'F') {
            // Only reward completion with at least D rank
            $goldReward = (int) (((int) ($score / GameConfig::RHYTHM_SCORE_GOLD_DIV) + GameConfig::RHYTHM_BASE_REWARD) * $rewardMultiplier);
            $expReward = (int) (((int) ($score / GameConfig::RHYTHM_SCORE_EXP_DIV) + GameConfig::RHYTHM_BASE_REWARD) * $rewardMultiplier);
        } elseif ($isFail) {
            // Fail: minimal rewards only if played enough & under daily cap
            $goldReward = (int) (2 * $rewardMultiplier);
            $expReward = (int) (2 * $rewardMultiplier);
        }

        // Pet mood boost: win = +15, fail = +5
        $moodBoost = (!$isFail && $rank !== 'F') ? GameConfig::RHYTHM_MOOD_BOOST_WIN : GameConfig::RHYTHM_MOOD_BOOST_FAIL;

        // Atomic transaction
        $this->db->transBegin();

        try {
            $this->rhythmService->submitScore([
                'user_id' => $this->userId,
                'song_id' => $songId,
                'score' => $score,
                'max_combo' => $maxCombo,
                'perfect_hits' => $perfectPlusHits + $perfectHits, // Merge P+ into perfect
                'great_hits' => $greatHits,
                'good_hits' => $goodHits,
                'miss_hits' => $missHits,
                'rank_grade' => $rank,
                'gold_earned' => $goldReward,
                'exp_earned' => $expReward,
            ]);

            // Gold Reward (only if > 0)
            if ($goldReward > 0) {
                $goldService = service('goldService');
                $goldService->addGoldRaw($this->userId, $goldReward, 'rhythm_reward', "Rank: {$rank}, Score: {$score}");
            }

            // Pet mood and exp (only if > 0)
            if ($expReward > 0 || $moodBoost > 0) {
                $petService = service('petService');
                $activePet = $petService->getActivePet($this->userId);

                if ($activePet) {
                    $petService->updateMood($activePet['id'], min(100, (int) $activePet['mood'] + $moodBoost));
                    if ($expReward > 0) {
                        $petService->addExpRaw($activePet['id'], $expReward);
                    }
                }
            }

            $this->db->transCommit();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            return $this->error('Failed to record score and rewards.', 500, 'DATABASE_ERROR');
        }

        $remainingPlays = max(0, GameConfig::RHYTHM_DAILY_PLAY_CAP - $todayPlays - 1);
        return $this->success([
            'score' => $score,
            'rank' => $rank,
            'gold_earned' => $goldReward,
            'exp_earned' => $expReward,
            'mood_earned' => $moodBoost,
            'remaining_rewarded_plays' => $remainingPlays,
        ], $rewardMultiplier > 0 ? "Score submitted!" : "Score recorded. Daily reward limit reached.");
    }

    public function highscore(): ResponseInterface
    {
        $songId = (int) ($this->request->getGet('song_id') ?? 0);
        if (!$songId)
            return $this->error('Song ID required', 400);

        $hs = $this->rhythmService->getHighscore($this->userId, $songId);
        return $this->success(['highscore' => $hs]);
    }
}
