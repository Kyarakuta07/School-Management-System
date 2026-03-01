<?php

namespace Tests\Unit\Pet;

use CodeIgniter\Test\CIUnitTestCase;
use App\Modules\Pet\Services\PetService;
use App\Modules\Pet\Models\PetModel;

/**
 * @internal
 */
final class PetServiceTest extends CIUnitTestCase
{
    private PetService $service;
    private $petModelMock;

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->createMock(\CodeIgniter\Database\BaseConnection::class);
        $this->service = new PetService($db);

        // We need to inject a mock model into the service
        // Since the service instantiates PetModel in constructor, 
        // we use Reflection to set the protected property
        $this->petModelMock = $this->createMock(PetModel::class);

        $reflection = new \ReflectionProperty(PetService::class, 'petModel');
        $reflection->setAccessible(true);
        $reflection->setValue($this->service, $this->petModelMock);
    }

    public function testUpdatePetFiltersDisallowedFields(): void
    {
        $petId = 123;
        $inputData = [
            'nickname' => 'NewName',
            'level' => 99, // Should be filtered
            'exp' => 1000, // Should be filtered
            'mood' => 80,
            'hack_gold' => 999999 // Should be filtered
        ];

        // Expectation: update() is called only with allowed fields
        $this->petModelMock->expects($this->once())
            ->method('update')
            ->with($petId, [
                'nickname' => 'NewName',
                'mood' => 80
            ]);

        $this->service->updatePet($petId, $inputData);
    }

    public function testUpdatePetDoesNothingIfNoAllowedFields(): void
    {
        $petId = 123;
        $inputData = [
            'level' => 99,
            'exp' => 1000
        ];

        // Expectation: update() is NEVER called
        $this->petModelMock->expects($this->never())->method('update');

        $this->service->updatePet($petId, $inputData);
    }
}
