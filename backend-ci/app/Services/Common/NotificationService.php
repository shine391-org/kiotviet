<?php

namespace App\Services\Common;

/** Simple notification logger for alerts. @agent-service: Notification @agent-pattern: Log-based notifier @agent-reusable: LOW */
class NotificationService
{
    /** Send low/stock alert notification (stub to log file). */
    public function sendInventoryAlert(array $alert): bool
    {
        $logDir = WRITEPATH . 'logs';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0775, true);
        }
        $line = '[' . date('Y-m-d H:i:s') . '] inventory_alert ' . json_encode($alert) . PHP_EOL;
        return (bool) file_put_contents($logDir . '/inventory-alerts.log', $line, FILE_APPEND);
    }
}
