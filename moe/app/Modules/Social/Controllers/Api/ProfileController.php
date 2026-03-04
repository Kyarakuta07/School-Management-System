<?php

namespace App\Modules\Social\Controllers\Api;
use App\Kernel\BaseApiController;

use App\Kernel\BaseController;

/**
 * ProfileController â€” JSON API for profile photo upload and fun fact update.
 * Ported from legacy moe/user/update_profile.php.
 */
class ProfileController extends BaseApiController
{
    protected \App\Modules\User\Services\ActivityLogService $activityLog;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);
        $this->activityLog = service('activityLog');
    }

    public function update()
    {
        helper(['url', 'form', 'common']);
        if (!($this->request instanceof \CodeIgniter\HTTP\IncomingRequest)) {
            return $this->error('Invalid request type');
        }
        $userId = $this->userId;
        $db = $this->db;

        $action = $this->request->getPost('action');

        // Handle Fun Fact Update
        if ($action === 'update_funfact') {
            $funFact = trim($this->request->getPost('fun_fact') ?? '');

            if (strlen($funFact) > 500) {
                return $this->error('Fun fact terlalu panjang (max 500 karakter)');
            } else {
                $userModel = new \App\Modules\User\Models\UserModel();
                $updated = $userModel->update($userId, ['fun_fact' => $funFact]);

                if ($updated) {
                    $this->activityLog->log('PROFILE_UPDATE', 'USER', "Updated fun fact.", $userId);
                    return $this->success([
                        'fun_fact' => htmlspecialchars($funFact, ENT_QUOTES, 'UTF-8')
                    ], 'Fun fact berhasil diupdate!');
                } else {
                    return $this->error('Gagal menyimpan ke database');
                }
            }
        }

        // Handle Profile Photo Upload
        if ($action === 'upload_photo') {
            $file = $this->request->getFile('profile_photo');

            if (!$file || !$file->isValid()) {
                return $this->error('Tidak ada file yang diupload');
            }

            $maxSize = 2 * 1024 * 1024; // 2MB
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if ($file->getSize() > $maxSize) {
                return $this->error('Ukuran file terlalu besar (max 2MB)');
            }

            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $allowedTypes)) {
                return $this->error('Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP');
            }

            $uploadDir = FCPATH . 'assets/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $newFilename = 'profile_' . $userId . '_' . time() . '.' . $file->getExtension();
            $targetPath = $uploadDir . $newFilename;

            // Resize image to 150x150 square (only if GD extension is available)
            $resized = false;
            $targetSize = 150;

            if (\extension_loaded('gd')) {
                switch ($mimeType) {
                    case 'image/jpeg':
                        $sourceImage = \imagecreatefromjpeg($file->getTempName());
                        break;
                    case 'image/png':
                        $sourceImage = \imagecreatefrompng($file->getTempName());
                        break;
                    case 'image/gif':
                        $sourceImage = \imagecreatefromgif($file->getTempName());
                        break;
                    case 'image/webp':
                        $sourceImage = \imagecreatefromwebp($file->getTempName());
                        break;
                    default:
                        $sourceImage = false;
                }

                if ($sourceImage) {
                    $origWidth = \imagesx($sourceImage);
                    $origHeight = \imagesy($sourceImage);
                    $size = min($origWidth, $origHeight);
                    $srcX = ($origWidth - $size) / 2;
                    $srcY = ($origHeight - $size) / 2;

                    $newImage = \imagecreatetruecolor($targetSize, $targetSize);

                    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                        \imagealphablending($newImage, false);
                        \imagesavealpha($newImage, true);
                        $transparent = \imagecolorallocatealpha($newImage, 0, 0, 0, 127);
                        \imagefill($newImage, 0, 0, $transparent);
                    }

                    \imagecopyresampled($newImage, $sourceImage, 0, 0, $srcX, $srcY, $targetSize, $targetSize, $size, $size);

                    switch ($mimeType) {
                        case 'image/jpeg':
                            $resized = \imagejpeg($newImage, $targetPath, 90);
                            break;
                        case 'image/png':
                            $resized = \imagepng($newImage, $targetPath, 9);
                            break;
                        case 'image/gif':
                            $resized = \imagegif($newImage, $targetPath);
                            break;
                        case 'image/webp':
                            $resized = \imagewebp($newImage, $targetPath, 90);
                            break;
                        default:
                            $resized = false;
                    }

                    \imagedestroy($sourceImage);
                    \imagedestroy($newImage);
                }
            }

            if (!$resized) {
                $file->move($uploadDir, $newFilename);
                $resized = $file->hasMoved();
            }

            if ($resized) {
                // Delete old photo
                $userModel = new \App\Modules\User\Models\UserModel();
                $oldPhoto = $userModel->find($userId);

                if ($oldPhoto && !empty($oldPhoto['profile_photo']) && file_exists($uploadDir . $oldPhoto['profile_photo'])) {
                    unlink($uploadDir . $oldPhoto['profile_photo']);
                }

                $userModel->update($userId, ['profile_photo' => $newFilename]);


                // Invalidate sanctuary leaders cache so new photo appears immediately
                $freshUser = $userModel->find($userId);
                if (!empty($freshUser['id_sanctuary'])) {
                    cache()->delete('sanctuary_leaders_' . $freshUser['id_sanctuary']);
                }

                $this->activityLog->log('PHOTO_UPDATE', 'USER', "Updated profile photo.", $userId);

                return $this->success([
                    'photo_url' => 'assets/uploads/profiles/' . $newFilename
                ], 'Foto profil berhasil diupdate!');
            } else {
                return $this->error('Gagal mengupload file');
            }
        }

        return $this->error('Action tidak valid');
    }
}
