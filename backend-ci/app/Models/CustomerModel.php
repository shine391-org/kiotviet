<?php

namespace App\Models;

use CodeIgniter\Model;

/** Customers schema model. @agent-model: customers */
class CustomerModel extends Model
{
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'organization_id',
        'customer_group_id',
        'name',
        'email',
        'phone',
        'phone2',
        'gender',
        'facebook',
        'customer_type',
        'company_name',
        'tax_code',
        'buyer_name',
        'invoice_company_name',
        'invoice_address',
        'invoice_province',
        'invoice_district',
        'invoice_ward',
        'invoice_email',
        'invoice_phone',
        'cccd_cmnd',
        'id_number',
        'bank_account',
        'bank_name',
        'notes',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $useTimestamps = false;
}
