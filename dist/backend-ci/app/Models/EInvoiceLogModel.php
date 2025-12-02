<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * E-invoice log schema.
 *
 * @agent-model: e_invoice_logs
 * @agent-pattern: CI4 model
 */
class EInvoiceLogModel extends Model
{
    protected $table = 'e_invoice_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'invoice_id',
        'status',
        'payload',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
