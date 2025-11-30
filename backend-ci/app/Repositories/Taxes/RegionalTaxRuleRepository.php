<?php

namespace App\Repositories\Taxes;

use App\Models\RegionalTaxRuleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Regional tax rule repository.
 *
 * @agent-repository: Regional tax rule
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class RegionalTaxRuleRepository
{
    protected RegionalTaxRuleModel $rules;
    protected BaseConnection $db;

    public function __construct(?RegionalTaxRuleModel $rules = null, ?BaseConnection $db = null)
    {
        $this->rules = $rules ?? new RegionalTaxRuleModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function upsert(array $data): array
    {
        $existing = $this->findByCountry($data['country']);
        $payload = array_merge($data, ['rule_json' => json_encode($data['rule_json']), 'updated_at' => $this->now()]);
        if ($existing) {
            $this->db->table('regional_tax_rules')->where('id', $existing['id'])->update($payload);
            return $this->findByCountry($data['country']) ?? [];
        }
        $payload['created_at'] = $this->now();
        $this->db->table('regional_tax_rules')->insert($payload);
        $err = $this->db->error();
        if ($err['code'] ?? 0) {
            throw new \RuntimeException('Failed to save tax rule: ' . $err['message']);
        }
        return $this->findByCountry($data['country']) ?? [];
    }

    public function findByCountry(string $country): ?array
    {
        $row = $this->rules->where('country', $country)->first();
        if ($row && isset($row['rule_json'])) {
            $row['rule_json'] = json_decode($row['rule_json'], true);
        }
        return $row ?: null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
