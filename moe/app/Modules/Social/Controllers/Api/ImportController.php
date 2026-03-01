<?php

namespace App\Modules\Social\Controllers\Api;
use App\Kernel\BaseApiController;
use App\Modules\Social\Models\RhythmModel;

use CodeIgniter\HTTP\ResponseInterface;
use Exception;
use ZipArchive;

/**
 * ImportController - Handle osu! Beatmap Import
 * Ported from legacy with security enhancements.
 */
class ImportController extends BaseApiController
{
    protected RhythmModel $rhythmModel;

    public function __construct()
    {
        $this->rhythmModel = new RhythmModel();
    }

    /**
     * Upload and import .osz/.osu file
     * POST: /api/rhythm/import (multipart/form-data with 'osz_file')
     */
    public function importOsz(): ResponseInterface
    {
        // 1. Authorization check: Only Vasiki (admin)
        if (session()->get('role') !== ROLE_VASIKI) {
            return $this->error('Access denied. Admin only.', 403, 'FORBIDDEN');
        }

        // 2. File validation
        $file = $this->request->getFile('osz_file');
        if (!$file || !$file->isValid()) {
            return $this->error('No valid file uploaded', 400, 'VALIDATION_ERROR');
        }

        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['osz', 'osu', 'zip'])) {
            return $this->error('Invalid file type. Only .osz or .osu allowed.', 400, 'VALIDATION_ERROR');
        }

        // Additional MIME check for osz/zip
        if ($ext === 'osz' || $ext === 'zip') {
            $mime = $file->getMimeType();
            if ($mime !== 'application/zip' && $mime !== 'application/x-zip-compressed') {
                return $this->error('Invalid file format. Expected ZIP archive.', 400, 'VALIDATION_ERROR');
            }
        }

        // 3. File size guard — reject very large files early (max 10MB)
        $maxSize = 10 * 1024 * 1024; // 10MB
        if ($file->getSize() > $maxSize) {
            return $this->error('File too large. Maximum size is 10MB.', 400, 'VALIDATION_ERROR');
        }

        // 3b. Prepare temp directory
        $tempDir = WRITEPATH . 'temp/osu_import_' . uniqid();
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        try {
            if ($ext === 'osz' || $ext === 'zip') {
                if (!class_exists('ZipArchive')) {
                    throw new Exception('ZipArchive extension missing on server.');
                }

                $zip = new ZipArchive();
                if ($zip->open($file->getTempName()) !== true) {
                    throw new Exception('Failed to open .osz file');
                }

                // Extract with safety check: avoid ZipSlip/Path Traversal
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (strpos($entryName, '..') !== false || $entryName[0] === '/' || $entryName[0] === '\\') {
                        continue; // Skip dangerous paths
                    }
                    $zip->extractTo($tempDir, $entryName);
                }
                $zip->close();
            } else {
                // Single .osu file
                $file->move($tempDir, $file->getClientName());
            }

            // 4. Find .osu files
            $osuFiles = glob($tempDir . '/*.osu');
            if (empty($osuFiles)) {
                throw new Exception('No .osu file found in package');
            }

            // 5. Find audio file
            $audioFile = null;
            foreach (['mp3', 'ogg', 'wav'] as $audioExt) {
                $found = glob($tempDir . '/*.' . $audioExt);
                if (!empty($found)) {
                    $audioFile = $found[0];
                    break;
                }
            }

            // 6. Parse the first .osu file
            $osuData = $this->parseOsuFile($osuFiles[0]);

            // 7. Process Audio
            $audioFileName = null;
            if ($audioFile) {
                $audioFileName = 'imported_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . pathinfo($audioFile, PATHINFO_EXTENSION);
                $musicDir = FCPATH . 'assets/music/'; // Public music directory
                if (!is_dir($musicDir)) {
                    mkdir($musicDir, 0755, true);
                }
                copy($audioFile, $musicDir . $audioFileName);
            }

            // 8. DB Insertion via RhythmModel (Transactional)
            $title = $osuData['title'] ?: 'Unknown';
            $artist = $osuData['artist'] ?: 'Unknown';
            $bpm = $osuData['bpm'] ?: 120;
            $duration = $osuData['duration'] ?: 180;
            $diffName = $osuData['version'] ?: 'Normal';
            $noteCount = count($osuData['notes']);
            $beatmapJson = json_encode($osuData['notes']);

            $songId = $this->rhythmModel->importSongWithBeatmap(
                [
                    'title' => $title,
                    'artist' => $artist,
                    'audio_file' => $audioFileName,
                    'bpm' => $bpm,
                    'duration_sec' => $duration,
                    'difficulty' => 'Expert',
                    'is_active' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                ],
                [
                    'difficulty_name' => $diffName,
                    'note_count' => $noteCount,
                    'beatmap_data' => $beatmapJson,
                    'created_at' => date('Y-m-d H:i:s'),
                ]
            );

            if (!$songId) {
                throw new Exception('Database transaction failed');
            }

            // 9. Cleanup
            $this->deleteDir($tempDir);

            return $this->success([
                'song_id' => $songId,
                'title' => $title,
                'artist' => $artist,
                'note_count' => $noteCount,
                'difficulty' => $diffName
            ], 'Import Successful!');

        } catch (Exception $e) {
            $this->deleteDir($tempDir);
            log_message('error', '[ImportController] ' . $e->getMessage());
            return $this->error('Import gagal. Silakan coba lagi.', 500, 'IMPORT_ERROR');
        }
    }

    /**
     * Ported parser from legacy
     */
    private function parseOsuFile($filePath): array
    {
        $content = file_get_contents($filePath);
        if (!$content)
            return [];

        $lines = explode("\n", $content);
        $data = [
            'title' => '',
            'artist' => '',
            'version' => '',
            'bpm' => 120,
            'duration' => 180,
            'notes' => []
        ];

        $section = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line))
                continue;

            if (preg_match('/^\[(.+)\]$/', $line, $matches)) {
                $section = $matches[1];
                continue;
            }

            if ($section === 'Metadata') {
                if (strpos($line, 'Title:') === 0)
                    $data['title'] = trim(substr($line, 6));
                elseif (strpos($line, 'Artist:') === 0)
                    $data['artist'] = trim(substr($line, 7));
                elseif (strpos($line, 'Version:') === 0)
                    $data['version'] = trim(substr($line, 8));
            }

            if ($section === 'TimingPoints') {
                $parts = explode(',', $line);
                if (count($parts) >= 2 && floatval($parts[1]) > 0) {
                    $beatLength = floatval($parts[1]);
                    $bpm = round(60000 / $beatLength);
                    if ($bpm > 0 && $bpm < 400)
                        $data['bpm'] = $bpm;
                }
            }

            if ($section === 'HitObjects') {
                $parts = explode(',', $line);
                if (count($parts) >= 4) {
                    $x = intval($parts[0]);
                    $time = intval($parts[2]);
                    $type = intval($parts[3]);
                    $lane = min(3, max(0, floor($x / 128)));
                    $isHold = ($type & 128) !== 0;
                    $duration = 0;

                    if ($isHold && isset($parts[5])) {
                        $endTime = explode(':', $parts[5])[0];
                        $duration = max(0, intval($endTime) - $time);
                    }

                    $data['notes'][] = [
                        'time' => $time,
                        'lane' => $lane,
                        'type' => $isHold ? 'hold' : 'tap',
                        'duration' => $duration
                    ];

                    if ($time > $data['duration'] * 1000) {
                        $data['duration'] = ceil($time / 1000) + 5;
                    }
                }
            }
        }

        usort($data['notes'], fn($a, $b) => $a['time'] - $b['time']);
        return $data;
    }

    private function deleteDir($dir): void
    {
        if (!is_dir($dir))
            return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}
