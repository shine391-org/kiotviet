<?php

namespace App\Repositories\Taxes;

use App\Models\TaxCertificateModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Tax certificate repository.
 *
 * @agent-repository: Tax certificate
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class TaxCertificateRepository
{
    protected TaxCertificateModel $certs;
    protected BaseConnection $db;

    public function __construct(?TaxCertificateModel $certs = null, ?BaseConnection $db = null)
    {
        $this->certs = $certs ?? new TaxCertificateModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->certs->insert($payload);
        $payload['id'] = (int) $this->certs->getInsertID();
        return $payload;
    }

    public function nextNumber(string $country): string
    {
        $prefix = 'CERT-' . strtoupper($country) . '-' . date('Ymd');
        $count = $this->certs->where('certificate_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
