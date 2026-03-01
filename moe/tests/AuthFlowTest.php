<?php

namespace Tests;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

class AuthFlowTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock activityLog to prevent database connection during test
        $activityLog = $this->createMock(\App\Modules\User\Services\ActivityLogService::class);
        \Config\Services::injectMock('activityLog', $activityLog);

        // Mock the database service to be safe
        $db = $this->createMock(\CodeIgniter\Database\BaseConnection::class);
        \Config\Services::injectMock('database', $db);
    }

    /**
     * Test that the login page renders correctly.
     */
    public function testShowLoginRendersCorrectly(): void
    {
        $result = $this->get('/login');

        $result->assertStatus(200);
        $result->assertSee('MEDITERRANEAN OF EGYPT');
        $result->assertSee('ENTER YOUR CREDENTIALS');
    }

    /**
     * Test showRegister renders correctly.
     */
    public function testShowRegisterRendersCorrectly(): void
    {
        $result = $this->get('/register');

        $result->assertStatus(200);
        $result->assertSee('REGISTRASI ANGGOTA BARU');
    }
}
