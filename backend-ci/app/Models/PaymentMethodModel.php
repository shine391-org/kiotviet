<?php

namespace App\Models;

use CodeIgniter\Model;

/** Payment methods schema model. @agent-model: payment_methods */
class PaymentMethodModel extends Model
{
    protected $table = 'payment_methods';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'code',
        'name',
        'name_translations',
        'description',
        'is_active',
        'display_order',
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    protected $useTimestamps = false;
}
