<?php

namespace Tests\Services;

use App\Services\Common\NotificationService;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * @agent-test: NotificationService
 * @agent-pattern: File-based notifier test
 */
class NotificationServiceTest extends CIUnitTestCase
{
    private NotificationService $service;
    private string $logPath;
    private ?string $originalContent = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
        $this->logPath = WRITEPATH . 'logs/inventory-alerts.log';
        if (is_file($this->logPath)) {
            $this->originalContent = file_get_contents($this->logPath);
        }
    }

    protected function tearDown(): void
    {
        if ($this->originalContent !== null) {
            file_put_contents($this->logPath, $this->originalContent);
        } elseif (is_file($this->logPath)) {
            @unlink($this->logPath);
        }
        parent::tearDown();
    }

    public function testSendInventoryAlertWritesLogLine(): void
    {
        $alert = ['sku' => 'TEST-ALERT', 'qty' => 2];

        $result = $this->service->sendInventoryAlert($alert);

        $this->assertTrue($result);
        $this->assertFileExists($this->logPath);
        $content = file_get_contents($this->logPath);
        $this->assertStringContainsString('inventory_alert', $content);
        $this->assertStringContainsString('TEST-ALERT', $content);
    }
}
