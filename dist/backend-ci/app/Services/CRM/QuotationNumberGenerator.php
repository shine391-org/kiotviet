<?php

namespace App\Services\CRM;

use Config\Database;

/** @agent-service: Quotation number generator @agent-pattern: Counter per table @agent-reusable: LOW */
class QuotationNumberGenerator
{
    public function generate(): string
    {
        $db = Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $db->transStart();
        $row = $db->query('SELECT COUNT(*) AS cnt FROM ' . $db->protectIdentifiers('quotations') . ' FOR UPDATE')->getRowArray();
        $counter = (int) ($row['cnt'] ?? 0) + 1;
        $number = sprintf('Q-%06d', $counter);
        $db->transComplete();
        return $number;
    }
}
