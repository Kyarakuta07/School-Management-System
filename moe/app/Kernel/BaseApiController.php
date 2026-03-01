<?php

namespace App\Kernel;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Base API Controller
 * 
 * All API controllers extend this class.
 * 
 * Provides:
 * - JSON response helpers (success/error)
 * - User ID from session
 * - Gold management helpers (via GoldServiceInterface)
 * - Pet ownership verification (via PetServiceInterface)
 * 
 * NOTE: No domain model imports here — all cross-domain access
 * goes through service container (service('goldService'), etc.)
 */
abstract class BaseApiController extends Controller
{
    protected int $userId;
    protected $db;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->userId = (int) (session('id_nethera') ?? 0);
        $this->db = \Config\Database::connect();

        log_message('debug', '[BaseApiController] Init. UserID: ' . $this->userId);
    }

    protected function getUserId(): int
    {
        return (int) ($this->userId ?? 0);
    }

    // ==================================================
    // JSON RESPONSE HELPERS
    // ==================================================

    /**
     * Send success JSON response
     * @param bool $closeSession Set to true for read-only requests to prevent session locking
     */
    protected function success(array $data = [], ?string $message = null, bool $closeSession = false): ResponseInterface
    {
        if ($closeSession && session_id()) {
            session_write_close();
        }

        $response = array_merge(['success' => true], $data);
        if ($message !== null) {
            $response['message'] = $message;
        }
        return $this->response->setJSON($response);
    }

    /**
     * Send error JSON response
     */
    protected function error(string $error, int $code = 400, string $errorCode = 'ERROR'): ResponseInterface
    {
        return $this->response
            ->setStatusCode($code)
            ->setJSON([
                'success' => false,
                'error' => $error,
                'error_code' => $errorCode,
            ]);
    }

    /**
     * Get JSON input from request body
     */
    protected function getInput(): array
    {
        $request = $this->request;
        if (method_exists($request, 'getJSON')) {
            return $request->getJSON(true) ?? [];
        }
        return [];
    }

    // ==================================================
    // GOLD HELPERS (via GoldServiceInterface — no domain model import)
    // ==================================================

    protected function getUserGold(): int
    {
        return service('goldService')->getBalance($this->userId);
    }

    protected function deductGold(int $amount): bool
    {
        return service('goldService')->subtractGoldRaw($this->userId, $amount, 'deduct', 'API deduction');
    }

    protected function addGold(int $amount): bool
    {
        return service('goldService')->addGoldRaw($this->userId, $amount, 'reward', 'API reward');
    }

    // ==================================================
    // PET OWNERSHIP (via PetServiceInterface — no domain model import)
    // ==================================================

    protected function verifyPetOwnership(int $petId): bool
    {
        return service('petService')->verifyOwnership($this->userId, $petId);
    }
}
