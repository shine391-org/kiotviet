<?php

namespace App\Services\Orders;

use Config\Database;

/**
 * Thread-safe order number generation.
 *
 * @agent-service: Order number generator
 * @agent-pattern: Counter per table
 * @agent-reusable: MEDIUM
 */
class OrderNumberGenerator
{
    public function generate(): string
    {
        $db = Database::connect();
        $db->transStart();
        $sql = 'SELECT COUNT(*) AS cnt FROM ' . $db->protectIdentifiers('orders');
        if (strtolower($db->DBDriver) !== 'sqlite3') {
            $sql .= ' FOR UPDATE';
        }
        $row = $db->query($sql)->getRowArray();
        $counter = (int) ($row['cnt'] ?? 0) + 1;
        $number = sprintf('ORD-%06d', $counter);
        $db->transComplete();
        return $number;
    }
}
