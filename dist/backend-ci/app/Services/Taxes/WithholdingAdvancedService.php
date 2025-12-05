<?php

namespace App\Services\Taxes;

use App\Repositories\Taxes\TaxCertificateRepository;
use App\Validators\RegionalTaxValidator;
use RuntimeException;

/**
 * Withholding advanced service.
 *
 * @agent-service: Withholding advanced
 * @agent-pattern: Certificate generation
 * @agent-reusable: MEDIUM
 */
class WithholdingAdvancedService
{
    protected TaxCertificateRepository $certs;
    protected RegionalTaxValidator $validator;

    public function __construct(?TaxCertificateRepository $certs = null, ?RegionalTaxValidator $validator = null)
    {
        $this->certs = $certs ?? new TaxCertificateRepository();
        $this->validator = $validator ?? new RegionalTaxValidator();
    }

    /** @agent-use: POST /api/taxes/withholding-certificates */
    public function createCertificate(array $input): array
    {
        $data = $this->validator->validateCertificate($input);
        $number = $this->certs->nextNumber($data['country']);
        $withheld = round($data['base_amount'] * $data['withheld_rate'] / 100, 2);
        $cert = $this->certs->create([
            'certificate_number' => $number,
            'country' => $data['country'],
            'party_type' => $data['party_type'] ?? null,
            'party_id' => $data['party_id'] ?? null,
            'base_amount' => $data['base_amount'],
            'withheld_amount' => $withheld,
            'issue_date' => $data['issue_date'] ?? date('Y-m-d'),
        ]);
        return ['success' => true, 'data' => $cert];
    }
}
