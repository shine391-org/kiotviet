<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Tax certificate schema.
 *
 * @agent-model: tax_certificate_records
 * @agent-pattern: CI4 model
 */
class TaxCertificateModel extends Model
{
    protected $table = 'tax_certificate_records';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'certificate_number',
        'country',
        'party_type',
        'party_id',
        'base_amount',
        'withheld_amount',
        'issue_date',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
