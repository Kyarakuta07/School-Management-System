<?php

namespace Tests\Unit\User;

use CodeIgniter\Test\CIUnitTestCase;
use App\Modules\User\Services\GoldService;

/**
 * @internal
 */
final class GoldServiceTest extends CIUnitTestCase
{
    private GoldService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Mock DB connection
        $db = $this->createMock(\CodeIgniter\Database\BaseConnection::class);
        $this->service = new GoldService($db);
    }

    public function testAddGoldRawWithZeroAmountReturnsTrue(): void
    {
        // GoldService::addGoldRaw returns true immediately if amount <= 0
        $result = $this->service->addGoldRaw(1, 0, 'test', 'desc');
        $this->assertTrue($result);
    }

    public function testSubtractGoldRawWithZeroAmountReturnsTrue(): void
    {
        // GoldService::subtractGoldRaw returns true immediately if amount <= 0
        $result = $this->service->subtractGoldRaw(1, 0, 'test', 'desc');
        $this->assertTrue($result);
    }

    public function testTransferGoldWithZeroReturnsFalse(): void
    {
        // GoldService::transferGold returns false immediately if amount <= 0
        $result = $this->service->transferGold(1, 2, 0, 'desc');
        $this->assertFalse($result);
    }
}
