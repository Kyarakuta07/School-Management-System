<?php

namespace Tests\Unit\Pet;

use CodeIgniter\Test\CIUnitTestCase;
use App\Modules\Pet\Services\GachaService;

/**
 * @internal
 */
final class GachaRarityTest extends CIUnitTestCase
{
    private GachaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $db = $this->createMock(\CodeIgniter\Database\ConnectionInterface::class);
        $this->service = new GachaService($db);
    }

    private function callRollRarity(int $type): string
    {
        $method = new \ReflectionMethod(GachaService::class, 'rollRarity');
        $method->setAccessible(true);
        return $method->invoke($this->service, $type);
    }

    public function testStandardGachaReturnsValidRarity(): void
    {
        $rarity = $this->callRollRarity(1); // Standard
        $this->assertContains($rarity, ['Common', 'Uncommon', 'Rare', 'Epic', 'Legendary', 'Mythical']);
    }

    public function testRareGachaExcludesCommon(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $rarity = $this->callRollRarity(2); // Rare+
            $this->assertNotEquals('Common', $rarity);
            $this->assertNotEquals('Uncommon', $rarity);
        }
    }

    public function testPremiumGachaGuaranteesEpicPlus(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $rarity = $this->callRollRarity(3); // Premium
            $this->assertContains($rarity, ['Epic', 'Legendary', 'Mythical']);
        }
    }

    public function testInvalidGachaTypeDefaultsToStandard(): void
    {
        $rarity = $this->callRollRarity(999);
        $this->assertContains($rarity, ['Common', 'Uncommon', 'Rare', 'Epic', 'Legendary', 'Mythical']);
    }
}
